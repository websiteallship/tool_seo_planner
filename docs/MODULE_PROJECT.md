# MODULE_PROJECT — Project Management (M1)

## Logic & Workflow Nghiệp Vụ

### Luồng Tạo Project Mới
```
Planner điền form:
  client_name, domain, niche, target_market, gsc_site_url
       ↓
ProjectService.create(data)
  → Generate project_id (UUID)
  → Tạo spreadsheet tĩnh "ToolSEO - {domain}" bên trong Google Drive Folder đã cấu hình
  → Lưu spreadsheet_id vào _projects tab (bên trong _ToolSEO_Master list)
  → Tạo _config tab và các module tabs (keywords, silo, v.v...)
  → Return project object
       ↓
UI redirect đến Dashboard của project mới
```

### Luồng Chuyển Đang Làm (Switch Project)
```
User click Project Switcher (top bar)
  → Dropdown list từ _projects (filter status != archived)
  → Chọn project → Update Alpine global state: currentProject
  → Tất cả modules auto-reload với projectId mới
  → URL không đổi (SPA internal state)
```

### Quy Tắc Nghiệp Vụ
- Mỗi project = 1 Google Sheet file riêng (không share sheet)
- Duplicate project: copy _config + template data, KHÔNG copy thực data
- Archive: chỉ ẩn khỏi danh sách, sheet vẫn còn trên Drive
- Delete: không có — chỉ archive (bảo toàn data)

---

## Database

**Master Sheet — Sheet: `_projects`**

| Column | Type | Notes |
|---|---|---|
| project_id | UUID | Primary key |
| client_name | STRING | |
| website_name | STRING | |
| domain | STRING | Không có `https://` |
| niche | STRING | |
| target_market | STRING | Vd: "Nha Trang, Khánh Hòa" |
| gsc_site_url | STRING | Vd: `sc-domain:example.com` |
| spreadsheet_id | STRING | Google Sheet ID |
| status | ENUM | `active`/`paused`/`completed`/`archived` |
| start_date | DATE | |
| planner | STRING | |
| gsc_sync_enabled | BOOLEAN | |
| gsc_last_sync | DATETIME | |
| notes | TEXT | |

---

## UI Features

- **Card grid view** danh sách projects (icon niche, status badge, stats mini)
- **Quick create form** inline (không redirect)
- **Project switcher** top bar dropdown với search
- **Status filter**: All / Active / Paused / Completed
- **Duplicate**: clone project với tên mới
- **Settings page**: edit project info, reset GSC, xem spreadsheet link

---

## Service API

```javascript
ProjectService.getAll()                    // List all projects
ProjectService.create(data)                // New project + sheet
ProjectService.update(projectId, data)     // Edit project info
ProjectService.archive(projectId)          // Soft delete
ProjectService.duplicate(projectId, name)  // Clone project
ProjectService.getSpreadsheetId(projectId) // Get sheet ID (helper)
```
