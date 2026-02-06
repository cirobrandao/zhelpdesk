<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TicketRepository;
use App\Domain\TicketPriority;
use App\Domain\TicketStatus;
use Psr\Http\Message\UploadedFileInterface;

class TicketService
{
    private TicketRepository $repo;
    private AuditService $audit;

    private array $priorities = [
        TicketPriority::LOW => 72,
        TicketPriority::MEDIUM => 48,
        TicketPriority::HIGH => 24,
        TicketPriority::URGENT => 4,
    ];

    public function __construct(TicketRepository $repo, AuditService $audit)
    {
        $this->repo = $repo;
        $this->audit = $audit;
    }

    public function listTickets(array $filters): array
    {
        return $this->repo->list($filters);
    }

    public function getTicket(int $id): array
    {
        $ticket = $this->repo->find($id);
        return $ticket ?: [];
    }

    public function createTicket(array $data, int $userId, array $files): int
    {
        $priority = $data['priority'] ?? 'medium';
        $hours = $this->priorities[$priority] ?? 48;
        $slaDue = date('Y-m-d H:i:s', time() + ($hours * 3600));
        $assigned = !empty($data['assigned_agent_id']) ? (int) $data['assigned_agent_id'] : null;

        $ticketId = $this->repo->create([
            'subject' => $data['subject'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => TicketStatus::OPEN,
            'priority' => $priority,
            'category' => $data['category'] ?? null,
            'created_by' => $userId,
            'assigned_agent_id' => $assigned,
            'sla_due_at' => $slaDue,
        ]);

        $tags = array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? ''))));
        if ($tags) {
            $this->repo->setTags($ticketId, $tags);
        }

        $messageId = $this->repo->addMessage($ticketId, $userId, (string) ($data['description'] ?? ''), false);
        $this->storeAttachments($ticketId, $messageId, $files);
        $this->repo->addEvent($ticketId, 'created', json_encode(['by' => $userId]));
        $this->audit->log($userId, 'ticket_created', ['ticket_id' => $ticketId]);

        return $ticketId;
    }

    public function replyToTicket(int $ticketId, int $userId, array $data, array $files): void
    {
        $message = (string) ($data['message'] ?? '');
        $internal = !empty($data['is_internal']);

        $messageId = $this->repo->addMessage($ticketId, $userId, $message, $internal);
        $this->storeAttachments($ticketId, $messageId, $files);
        $this->repo->addEvent($ticketId, 'reply', json_encode(['by' => $userId]));
        $this->audit->log($userId, 'ticket_reply', ['ticket_id' => $ticketId]);
    }

    public function closeTicket(int $ticketId, int $userId): void
    {
        $this->repo->setStatus($ticketId, TicketStatus::CLOSED);
        $this->repo->addEvent($ticketId, 'closed', json_encode(['by' => $userId]));
        $this->audit->log($userId, 'ticket_closed', ['ticket_id' => $ticketId]);
    }

    public function reopenTicket(int $ticketId, int $userId): void
    {
        $this->repo->setStatus($ticketId, TicketStatus::OPEN);
        $this->repo->addEvent($ticketId, 'reopened', json_encode(['by' => $userId]));
        $this->audit->log($userId, 'ticket_reopened', ['ticket_id' => $ticketId]);
    }

    public function statusCounters(): array
    {
        return $this->repo->statusCounters();
    }

    public function categories(): array
    {
        return ['General', 'Billing', 'Technical'];
    }

    public function priorities(): array
    {
        return TicketPriority::all();
    }

    private function storeAttachments(int $ticketId, int $messageId, array $files): void
    {
        if (!isset($files['attachments'])) {
            return;
        }

        $allowed = [
            'image/png',
            'image/jpeg',
            'application/pdf',
            'text/plain',
        ];

        $uploadDir = storage_path('uploads');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $attachments = $files['attachments'];
        if (!is_array($attachments)) {
            $attachments = [$attachments];
        }

        foreach ($attachments as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                continue;
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                continue;
            }
            $tmp = $file->getStream()->getMetadata('uri');
            $mime = mime_content_type($tmp ?: '') ?: 'application/octet-stream';
            if (!in_array($mime, $allowed, true)) {
                continue;
            }
            $random = bin2hex(random_bytes(16));
            $extension = pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION);
            $filename = $random . ($extension ? '.' . $extension : '');
            $file->moveTo($uploadDir . DIRECTORY_SEPARATOR . $filename);

            $this->repo->addAttachment($ticketId, $messageId, [
                'filename' => $filename,
                'original_name' => (string) $file->getClientFilename(),
                'mime_type' => $mime,
                'size' => $file->getSize(),
            ]);
        }
    }
}
