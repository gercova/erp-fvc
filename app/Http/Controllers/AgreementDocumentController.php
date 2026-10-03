<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementDocument;
use App\Models\ServiceDeliverable;
use App\Services\Agreements\AgreementFileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgreementDocumentController extends Controller
{
    public function __construct(
        protected AgreementFileService $fileService
    ) {}

    /**
     * Download an agreement document from private storage.
     */
    public function download(AgreementDocument $document): Response {
        return $this->fileService->downloadDocument($document, auth()->user());
    }

    /**
     * Download a service deliverable file from private storage.
     */
    public function downloadDeliverable(ServiceDeliverable $deliverable): Response {
        return $this->fileService->downloadDeliverable($deliverable, auth()->user());
    }

    /**
     * Upload a new document to an agreement.
     */
    public function upload(Request $request, Agreement $agreement) {
        $request->validate([
            'document'      => 'required|file|max:51200', // 50MB max
            'document_type' => 'required|string',
            'title'         => 'required|string|max:255',
        ]);

        $document = $this->fileService->storeDocument(
            $agreement,
            $request->file('document'),
            $request->input('document_type'),
            $request->input('title'),
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Documento cargado correctamente en almacenamiento privado.',
            'data'    => $document,
        ], 201);
    }
}
