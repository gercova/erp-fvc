<?php

namespace App\Notifications;

use App\Models\DocumentApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentPendingApprovalNotification extends Notification
{
    use Queueable;

    protected DocumentApproval $approval;
    protected string $documentTitle;
    protected string $requesterName;
    protected string $url;

    public function __construct(DocumentApproval $approval, string $documentTitle, string $requesterName, string $url) {
        $this->approval         = $approval;
        $this->documentTitle    = $documentTitle;
        $this->requesterName    = $requesterName;
        $this->url              = $url;
    }

    public function via($notifiable): array {
        return ['database'];
    }

    public function toArray($notifiable): array {
        return [
            'approval_id'       => $this->approval->id,
            'document_type'     => $this->approval->document_type,
            'document_id'       => $this->approval->document_id,
            'document_title'    => $this->documentTitle,
            'requester_name'    => $this->requesterName,
            'role_label'        => $this->approval->label,
            'message'           => "Se requiere su firma como {$this->approval->label} en: {$this->documentTitle} ({$this->requesterName})",
            'url'               => $this->url,
        ];
    }
}
