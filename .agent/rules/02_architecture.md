# Rule: Architecture — Layered System Design

## Layers (bottom-up)

```
Data Store          → Google Sheets · Google Drive
Infrastructure      → GSCClient.gs · GeminiClient.gs · Utils.gs · CacheService
Data Access Layer   → SheetDB.gs (generic CRUD — getRows, addRow, updateRow, batchAddRows, deleteRow)
Service Layer       → *Service.gs (business logic, 1 file per module, 14 services)
API Gateway         → API.gs (single action dispatcher)
Presentation        → index.html + Alpine.js + Shoelace
```

## Data Flow Chuẩn

```
User Action → Alpine.js → callServer(action, params)
  → google.script.run → API.gs dispatch()
  → *Service.gs → SheetDB.gs → Google Sheets
← {success: true, data: ...} ←
```

## File Structure

```
toolseo/
├── Code.gs              # doGet(), include()
├── Router.gs            # page routing
├── API.gs               # action dispatcher (switch)
├── Utils.gs             # UUID, date, validation
├── SheetDB.gs           # generic CRUD
├── GSCClient.gs         # GSC API v3 wrapper
├── GeminiClient.gs      # Gemini API wrapper
├── services/
│   └── *Service.gs      # 14 service files
├── html/
│   ├── index.html       # SPA shell
│   ├── css/styles.html
│   ├── js/
│   │   ├── app.html          # Alpine router + global state
│   │   ├── api-client.html   # google.script.run Promise wrapper
│   │   └── components.html   # Shoelace wrappers
│   └── pages/           # 14 module pages
├── templates/           # JSON checklist templates
└── docs/
```

## Google Sheets Structure

- **Master Sheet**: tab `_projects` (app-level, tất cả dự án)
- **Per-Project Sheet**: 1 file riêng/project, tạo tự động từ template
  - Lưu `spreadsheet_id` trong `_projects`

## GAS Quotas — BẮT BUỘC nhớ

| Limit | Value | Giải pháp |
|---|---|---|
| Execution time | 6 phút/call | Batch + trigger |
| URL Fetch | 20,000/ngày | Cache responses |
| Spreadsheet read | 300 req/phút | Batch read 1 lần |
| Script triggers | 20 total | Tối thiểu hoá |
| Cache size | 100KB/item | Compress nếu cần |
| Properties storage | 500KB total | Chỉ config nhỏ |
