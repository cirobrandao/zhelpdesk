<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class TicketRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function list(array $filters): array
    {
        $sql = 'SELECT t.* FROM tickets t WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND t.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= ' AND t.priority = :priority';
            $params['priority'] = $filters['priority'];
        }
        if (!empty($filters['agent_id'])) {
            $sql .= ' AND t.assigned_agent_id = :agent_id';
            $params['agent_id'] = (int) $filters['agent_id'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (t.subject LIKE :q OR t.description LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY t.updated_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tickets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $ticket = $stmt->fetch();
        if (!$ticket) {
            return null;
        }

        $ticket['messages'] = $this->messages($id);
        $ticket['events'] = $this->events($id);
        $ticket['attachments'] = $this->attachments($id);

        return $ticket;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO tickets (subject, description, status, priority, category, created_by, assigned_agent_id, sla_due_at, created_at, updated_at) VALUES (:subject, :description, :status, :priority, :category, :created_by, :assigned_agent_id, :sla_due_at, NOW(), NOW())');
        $stmt->execute([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => $data['status'],
            'priority' => $data['priority'],
            'category' => $data['category'],
            'created_by' => $data['created_by'],
            'assigned_agent_id' => $data['assigned_agent_id'],
            'sla_due_at' => $data['sla_due_at'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function addMessage(int $ticketId, int $userId, string $message, bool $internal): int
    {
        $stmt = $this->db->prepare('INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal, created_at) VALUES (:ticket_id, :user_id, :message, :is_internal, NOW())');
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'message' => $message,
            'is_internal' => $internal ? 1 : 0,
        ]);
        $this->touch($ticketId);
        return (int) $this->db->lastInsertId();
    }

    public function addEvent(int $ticketId, string $type, string $payload): void
    {
        $stmt = $this->db->prepare('INSERT INTO ticket_events (ticket_id, event_type, payload, created_at) VALUES (:ticket_id, :event_type, :payload, NOW())');
        $stmt->execute([
            'ticket_id' => $ticketId,
            'event_type' => $type,
            'payload' => $payload,
        ]);
        $this->touch($ticketId);
    }

    public function addAttachment(int $ticketId, int $messageId, array $file): void
    {
        $stmt = $this->db->prepare('INSERT INTO ticket_attachments (ticket_id, message_id, filename, original_name, mime_type, size, created_at) VALUES (:ticket_id, :message_id, :filename, :original_name, :mime_type, :size, NOW())');
        $stmt->execute([
            'ticket_id' => $ticketId,
            'message_id' => $messageId,
            'filename' => $file['filename'],
            'original_name' => $file['original_name'],
            'mime_type' => $file['mime_type'],
            'size' => $file['size'],
        ]);
    }

    public function setStatus(int $ticketId, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $ticketId]);
    }

    public function touch(int $ticketId): void
    {
        $stmt = $this->db->prepare('UPDATE tickets SET updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $ticketId]);
    }

    public function messages(int $ticketId): array
    {
        $stmt = $this->db->prepare('SELECT tm.*, u.name FROM ticket_messages tm JOIN users u ON u.id = tm.user_id WHERE tm.ticket_id = :id ORDER BY tm.created_at ASC');
        $stmt->execute(['id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public function events(int $ticketId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ticket_events WHERE ticket_id = :id ORDER BY created_at ASC');
        $stmt->execute(['id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public function attachments(int $ticketId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ticket_attachments WHERE ticket_id = :id');
        $stmt->execute(['id' => $ticketId]);
        return $stmt->fetchAll();
    }

    public function statusCounters(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) as total FROM tickets GROUP BY status');
        return $stmt->fetchAll();
    }

    public function averageResponseTime(): ?float
    {
        $stmt = $this->db->query("SELECT AVG(TIMESTAMPDIFF(SECOND, t.created_at, tm.created_at)) as avg_seconds
            FROM tickets t
            JOIN ticket_messages tm ON tm.ticket_id = t.id
            WHERE tm.is_internal = 0
            GROUP BY t.id
        ");
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return null;
        }
        $total = 0;
        $count = 0;
        foreach ($rows as $row) {
            $total += (float) $row['avg_seconds'];
            $count++;
        }
        return $count > 0 ? $total / $count : null;
    }

    public function averageResolutionTime(): ?float
    {
        $stmt = $this->db->query("SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds
            FROM tickets
            WHERE status IN ('resolved', 'closed')
        ");
        $row = $stmt->fetch();
        return $row ? (float) $row['avg_seconds'] : null;
    }
}
