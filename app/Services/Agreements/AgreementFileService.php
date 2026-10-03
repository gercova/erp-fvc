<?php

namespace App\Services\Agreements;

use App\Enums\AgreementDocumentType;
use App\Models\Agreement;
use App\Models\AgreementDocument;
use App\Models\ServiceDeliverable;
use App\Models\ServiceEngagement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AgreementFileService
{
    protected string $disk = 'local'; // Private storage (storage/app)

    /**
     * Store an agreement document in private storage.
     */
    public function storeDocument(
        Agreement $agreement,
        UploadedFile $file,
        string|AgreementDocumentType $type,
        string $title,
        ?User $user = null
    ): AgreementDocument {
        $safeCode = Str::slug($agreement->code, '-');
        $directory = "agreements/{$safeCode}/documents";
        
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $path = $file->storeAs($directory, $storedName, $this->disk);

        $documentType = is_string($type) ? AgreementDocumentType::tryFrom($type) ?? AgreementDocumentType::OTHER : $type;

        $maxVersion = AgreementDocument::where('agreement_id', $agreement->id)
            ->where('document_type', $documentType->value)
            ->max('version');

        return AgreementDocument::create([
            'agreement_id'         => $agreement->id,
            'document_type'        => $documentType,
            'title'                => $title,
            'file_path'            => $path,
            'file_name'            => $file->getClientOriginalName() ?: $storedName,
            'file_size'            => $file->getSize(),
            'mime_type'            => $file->getClientMimeType() ?: 'application/octet-stream',
            'uploaded_by_user_id'  => $user?->id ?? auth()->id(),
            'version'              => ($maxVersion ?? 0) + 1,
        ]);
    }

    /**
     * Store a deliverable evidence file in private storage.
     */
    public function storeDeliverableFile(
        ServiceDeliverable $deliverable,
        UploadedFile $file,
        ?User $user = null
    ): ServiceDeliverable {
        $engagement = $deliverable->engagement;
        $safeCode = $engagement ? Str::slug($engagement->code, '-') : 'general';
        $directory = "agreements/deliverables/{$safeCode}";

        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $path = $file->storeAs($directory, $storedName, $this->disk);

        $deliverable->update([
            'file_path'       => $path,
            'submission_date' => now()->toDateString(),
        ]);

        return $deliverable;
    }

    /**
     * Store an obligation evidence file in private storage.
     */
    public function storeObligationEvidence(
        \App\Models\AgreementObligation $obligation,
        UploadedFile $file,
        ?User $user = null
    ): \App\Models\AgreementObligation {
        $agreement = $obligation->agreement;
        $safeCode = $agreement ? Str::slug($agreement->code, '-') : 'general';
        $directory = "agreements/{$safeCode}/obligations";

        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $path = $file->storeAs($directory, $storedName, $this->disk);

        $obligation->update([
            'evidence_file_path' => $path,
            'evidence'           => $file->getClientOriginalName() ?: $storedName,
        ]);

        return $obligation;
    }

    /**
     * Authorize and download an agreement document.
     */
    public function downloadDocument(AgreementDocument $document, ?User $user = null): StreamedResponse {
        $user = $user ?? auth()->user();

        if (!$this->canUserAccessDocument($document, $user)) {
            throw new AccessDeniedHttpException('No tiene permisos para descargar este documento de convenio.');
        }

        if (!Storage::disk($this->disk)->exists($document->file_path)) {
            throw new NotFoundHttpException('El archivo físico no fue encontrado en el almacenamiento privado.');
        }

        $filename = Str::slug($document->title, '-') . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION);

        return Storage::disk($this->disk)->download($document->file_path, $filename, [
            'Content-Type' => $document->mime_type ?? 'application/octet-stream',
        ]);
    }

    /**
     * Authorize and download a service deliverable file.
     */
    public function downloadDeliverable(ServiceDeliverable $deliverable, ?User $user = null): StreamedResponse {
        $user = $user ?? auth()->user();

        if (!$this->canUserAccessDeliverable($deliverable, $user)) {
            throw new AccessDeniedHttpException('No tiene permisos para descargar este entregable del servicio.');
        }

        if (empty($deliverable->file_path) || !Storage::disk($this->disk)->exists($deliverable->file_path)) {
            throw new NotFoundHttpException('El archivo físico del entregable no fue encontrado en el almacenamiento privado.');
        }

        $filename = Str::slug($deliverable->deliverable_name, '-') . '.' . pathinfo($deliverable->file_path, PATHINFO_EXTENSION);

        return Storage::disk($this->disk)->download($deliverable->file_path, $filename);
    }

    /**
     * Check if user is authorized to access the agreement document.
     */
    public function canUserAccessDocument(AgreementDocument $document, ?User $user): bool {
        if (!$user) {
            return false;
        }

        // Superadmin or admin
        if ($user->hasRole(['SUPERADMIN', 'ADMIN', 'DIRECTOR_GENERAL'])) {
            return true;
        }

        // User with permission agreements.view or agreements.manage
        if ($user->can('agreements.view') || $user->can('agreements.manage')) {
            return true;
        }

        // User who uploaded the document
        if ($document->uploaded_by_user_id === $user->id) {
            return true;
        }

        // Agreement coordinator
        $agreement = $document->agreement;
        if ($agreement && $agreement->coordinator_user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is authorized to access the service deliverable.
     */
    public function canUserAccessDeliverable(ServiceDeliverable $deliverable, ?User $user): bool {
        if (!$user) {
            return false;
        }

        if ($user->hasRole(['SUPERADMIN', 'ADMIN', 'DIRECTOR_GENERAL'])) {
            return true;
        }

        if ($user->can('agreements.view') || $user->can('services.view') || $user->can('agreements.manage')) {
            return true;
        }

        $engagement = $deliverable->engagement;
        if ($engagement) {
            if ($engagement->responsible_user_id === $user->id) {
                return true;
            }
            if ($engagement->agreement && $engagement->agreement->coordinator_user_id === $user->id) {
                return true;
            }
        }

        if ($deliverable->approved_by_user_id === $user->id) {
            return true;
        }

        return false;
    }
}
