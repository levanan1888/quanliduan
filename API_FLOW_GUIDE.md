# API Flow Guide - Project Management System

## Luồng hoạt động: PM → Project → Sprint → Task

### Bước 1: Authentication (Bắt buộc)

#### 1.1. Register hoặc Login để lấy token

**Option A: Register (Tạo tài khoản mới)**
```bash
POST http://localhost:8080/api/auth/register
Content-Type: application/json

{
  "full_name": "Project Manager",
  "title": "Senior PM",
  "email": "pm@example.com",
  "password": "password123",
  "role": "PM"
}
```

**Response:**
```json
{
  "access_token": "1|abc123xyz...",
  "refresh_token": "xyz789abc...",
  "token_type": "Bearer",
  "user": {...}
}
```

**Option B: Login (Nếu đã có tài khoản)**
```bash
POST http://localhost:8080/api/auth/login
Content-Type: application/json

{
  "email": "pm@example.com",
  "password": "password"
}
```

**Response:** Tương tự như register

**Lưu token:**
- Copy `access_token` để dùng cho các request sau
- Hoặc set vào Postman variable `access_token`

---

### Bước 2: PM tạo Project

```bash
POST http://localhost:8080/api/projects
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "name": "Website Redesign Project",
  "description": "Redesign company website with modern UI/UX",
  "status": "active",
  "start_date": "2025-11-01",
  "end_date": "2025-12-31",
  "member_ids": [2, 3, 4]
}
```

**Response:**
```json
{
  "id": 1,
  "name": "Website Redesign Project",
  "manager_id": 1,
  "members": [...],
  ...
}
```

**Lưu `project_id` = 1 để dùng tiếp**

---

### Bước 3: PM tạo Sprint cho Project

```bash
POST http://localhost:8080/api/projects/1/sprints
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "name": "Sprint 1 - Foundation",
  "start_date": "2025-11-01",
  "end_date": "2025-11-14",
  "status": "planned"
}
```

**Response:**
```json
{
  "id": 1,
  "project_id": 1,
  "name": "Sprint 1 - Foundation",
  "status": "planned",
  ...
}
```

**Lưu `sprint_id` = 1 để dùng tiếp**

---

### Bước 4: Tạo Task (có thể gắn Sprint hoặc để Backlog)

#### Option A: Tạo Task trong Sprint

```bash
POST http://localhost:8080/api/tasks
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "project_id": 1,
  "sprint_id": 1,
  "title": "Design homepage wireframe",
  "date": "2025-11-05",
  "priority": "HIGH",
  "status": "TO_DO",
  "assigned_to": 2
}
```

#### Option B: Tạo Task Backlog (không gắn Sprint)

```bash
POST http://localhost:8080/api/tasks
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "project_id": 1,
  "sprint_id": null,
  "title": "Future feature: Dark mode",
  "priority": "LOW",
  "status": "TO_DO"
}
```

**Response:**
```json
{
  "id": 1,
  "project_id": 1,
  "sprint_id": 1,
  "title": "Design homepage wireframe",
  "assigned_to": 2,
  ...
}
```

**Lưu `task_id` = 1**

---

### Bước 5: Quản lý Task

#### 5.1. Upload ảnh cho Task

```bash
POST http://localhost:8080/api/tasks/1/assets
Authorization: Bearer {access_token}
Content-Type: multipart/form-data

image: [file upload]
```

#### 5.2. Update Task status

```bash
PUT http://localhost:8080/api/tasks/1
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "status": "IN_PROGRESS"
}
```

#### 5.3. Xem Task chi tiết (bao gồm activities, subtasks, assets)

```bash
GET http://localhost:8080/api/tasks/1
Authorization: Bearer {access_token}
```

---

### Bước 6: Tạo SubTask cho Task

```bash
POST http://localhost:8080/api/tasks/1/sub-tasks
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "title": "Research color schemes",
  "date": "2025-11-03",
  "tag": "design"
}
```

**Response:**
```json
{
  "id": 1,
  "task_id": 1,
  "title": "Research color schemes",
  "is_completed": false,
  ...
}
```

---

### Bước 7: Xem và quản lý Notifications

#### 7.1. Xem danh sách notifications

```bash
GET http://localhost:8080/api/notifications
Authorization: Bearer {access_token}
```

#### 7.2. Đếm notifications chưa đọc

```bash
GET http://localhost:8080/api/notifications/unread-count
Authorization: Bearer {access_token}
```

#### 7.3. Đánh dấu tất cả đã đọc

```bash
POST http://localhost:8080/api/notifications/mark-all-read
Authorization: Bearer {access_token}
```

---

## Luồng hoạt động tổng quát

```
1. Login/Register
   ↓
2. PM tạo Project
   ↓
3. PM tạo Sprint (thuộc Project)
   ↓
4. Tạo Task (thuộc Project, có thể thuộc Sprint hoặc Backlog)
   ↓
5. Assign Task cho MEMBER
   ↓
6. MEMBER nhận notification
   ↓
7. MEMBER làm Task → Update status
   ↓
8. Tạo SubTask cho Task
   ↓
9. Upload assets (ảnh) cho Task
   ↓
10. Task Activities tự động được log
```

---

## Endpoints quan trọng theo role

### PM (Project Manager):
1. `POST /api/auth/login` - Login
2. `POST /api/projects` - Tạo project
3. `POST /api/projects/{id}/sprints` - Tạo sprint
4. `POST /api/tasks` - Tạo task
5. `GET /api/projects` - Xem tất cả projects
6. `GET /api/tasks` - Xem tất cả tasks

### MEMBER:
1. `POST /api/auth/login` - Login
2. `GET /api/tasks?assigned_to={user_id}` - Xem tasks được assign
3. `GET /api/projects` - Chỉ thấy projects mình là member
4. `PUT /api/tasks/{id}` - Update task status
5. `POST /api/tasks/{id}/sub-tasks` - Tạo subtask

---

## Quick Start Script (cURL)

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"pm@example.com","password":"password"}' \
  | jq -r '.access_token')

# 2. Tạo Project
PROJECT_ID=$(curl -s -X POST http://localhost:8080/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My Project","description":"Test project"}' \
  | jq -r '.id')

# 3. Tạo Sprint
SPRINT_ID=$(curl -s -X POST http://localhost:8080/api/projects/$PROJECT_ID/sprints \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Sprint 1","start_date":"2025-11-01","end_date":"2025-11-14"}' \
  | jq -r '.id')

# 4. Tạo Task
TASK_ID=$(curl -s -X POST http://localhost:8080/api/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"project_id\":$PROJECT_ID,\"sprint_id\":$SPRINT_ID,\"title\":\"Test Task\"}" \
  | jq -r '.id')

echo "Project ID: $PROJECT_ID"
echo "Sprint ID: $SPRINT_ID"
echo "Task ID: $TASK_ID"
```

---

## Postman Collection Variables

Sau khi import Postman Collection, set các variables:
- `base_url`: `localhost:8080`
- `access_token`: Token từ login response

Sau đó tất cả requests sẽ tự động sử dụng token.

