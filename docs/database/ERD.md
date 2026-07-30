# ERD Backend To Do List KAI

Diagram ini mengikuti migration yang aktif per 31 Juli 2026. Kalender tidak
memiliki tabel tersendiri; event kalender dibentuk dari `todos.deadline_at`.

```mermaid
erDiagram
    users ||--o{ workspaces : creates
    users ||--o{ workspace_members : joins
    workspaces ||--o{ workspace_members : contains
    users ||--o{ team_invites : creates
    workspaces ||--o{ team_invites : issues
    workspaces ||--o{ categories : owns_custom
    users ||--o{ categories : creates
    workspaces ||--o{ todos : contains
    users ||--o{ todos : creates
    categories ||--o{ todos : classifies
    todos ||--o{ todo_reminders : schedules
    todo_reminders ||--o{ reminder_deliveries : tracks
    users ||--o{ reminder_deliveries : receives
    workspaces ||--o{ sticky_notes : contains
    users ||--o{ sticky_notes : creates
    todos o|--o{ sticky_notes : converted_from
    workspaces o|--o{ activity_logs : archives
    users o|--o{ activity_logs : acts

    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
    }
    workspaces {
        bigint id PK
        bigint created_by FK
        string name
        string type
        tinyint member_limit
    }
    workspace_members {
        bigint id PK
        bigint workspace_id FK
        bigint user_id FK
        string role
        timestamp joined_at
    }
    team_invites {
        bigint id PK
        bigint workspace_id FK
        bigint created_by FK
        char token_hash UK
        timestamp expires_at
        timestamp revoked_at
    }
    categories {
        bigint id PK
        bigint workspace_id FK
        bigint created_by FK
        string name
        string slug
        boolean is_system
    }
    todos {
        bigint id PK
        bigint workspace_id FK
        bigint created_by FK
        bigint category_id FK
        string title
        text description
        string status
        datetime deadline_at
    }
    todo_reminders {
        bigint id PK
        bigint todo_id FK
        string kind
        datetime scheduled_at
        string status
        timestamp cancelled_at
    }
    reminder_deliveries {
        bigint id PK
        bigint reminder_id FK
        bigint user_id FK
        string status
        tinyint attempts
        timestamp sent_at
        timestamp failed_at
        text last_error
    }
    sticky_notes {
        bigint id PK
        bigint workspace_id FK
        bigint created_by FK
        bigint converted_to_todo_id FK
        text content
        string color
        timestamp converted_at
    }
    activity_logs {
        bigint id PK
        bigint workspace_id FK
        bigint actor_id FK
        string action
        string subject_type
        bigint subject_id
        json snapshot
        json changes
        timestamp created_at
    }
```

## Constraint penting

- Satu user hanya satu kali menjadi anggota workspace yang sama.
- Satu kode tim aktif disimpan sebagai SHA-256 hash; kode mentah hanya muncul
  pada response saat dibuat.
- Satu delivery hanya boleh ada sekali untuk pasangan reminder dan user.
- Kategori yang sedang dipakai task memakai foreign key `RESTRICT`.
- Penghapusan workspace menghapus data operasional, tetapi foreign key activity
  log menjadi `NULL` agar snapshot arsip tetap ada.
- Deadline dan waktu reminder disimpan UTC; boundary input/output memakai
  `Asia/Jakarta`.
