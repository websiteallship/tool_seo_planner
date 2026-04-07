---
description: Cập nhật docs khi phát sinh tính năng mới — đồng bộ CORE_FEATURES, MODULE_*, TRACK_CHANGELOG, TRACK_ROADMAP
---

# Workflow: Update Feature Docs

> Chạy workflow này MỖI KHI: thêm tính năng mới, thay đổi behavior module, hoặc thêm schema mới.

## Rules Cần Đọc Trước
- `.agent/rules/01_project_overview.md`
- `.agent/rules/02_architecture.md`
- `.agent/rules/07_database_schema.md`
- `.agent/rules/13_modules.md`

## Bản Đồ Docs → File

| Thay đổi thuộc về | File cần cập nhật |
|---|---|
| Tính năng mới bất kỳ module | `docs/CORE_FEATURES.md` |
| Module M1 Project | `docs/MODULE_PROJECT.md` |
| Module M2 Keyword | `docs/MODULE_KEYWORD.md` |
| Module M3 Silo | `docs/MODULE_SILO.md` |
| Module M4 Outline | `docs/MODULE_OUTLINE.md` |
| Module M5-M7 Audit | `docs/MODULE_AUDIT.md` |
| Module M10 GSC | `docs/MODULE_GSC.md` |
| Module M11-M12 Dashboard/Report | `docs/MODULE_DASHBOARD.md` / `docs/MODULE_REPORTING.md` |
| Database schema (sheet columns) | `docs/CORE_DATABASE.md` |
| API endpoints mới | `docs/CORE_ARCHITECTURE.md` |
| Architectural decision | `docs/TRACK_DECISIONS.md` |
| Release / version bump | `docs/TRACK_CHANGELOG.md` → chạy workflow `update-changelog` |

## Các Bước

### 1. Xác định phạm vi thay đổi
Trả lời 3 câu hỏi:
- Tính năng này thuộc **module nào**? (M1–M14)
- Có thay đổi **database schema** không? (thêm/sửa column/sheet)
- Có thay đổi **API action** không? (thêm action trong `API.gs`)

### 2. Cập nhật `CORE_FEATURES.md`
- Tìm đúng Phase và Module
- Thêm dòng mới: `- [ ] Mô tả tính năng` (nếu planned) hoặc `- [x]` (nếu done)
- Giữ format checkbox, không break structure hiện tại

### 3. Cập nhật `MODULE_*.md` tương ứng
Cấu trúc chuẩn mỗi section tính năng:
```markdown
### Tên Tính Năng
**Mục đích:** ...
**Trigger:** User action / Auto / AI
**Input:** ...
**Output / Side effect:** ...
**Business rule:** ...
**Edge cases:** ...
```

Nếu file module chưa có section → thêm theo đúng cấu trúc trên.

### 4. Cập nhật `CORE_DATABASE.md` (nếu có schema change)
- Thêm column mới vào đúng bảng/sheet
- Ghi rõ: tên column, kiểu dữ liệu, mô tả, default value
- Nếu thêm sheet mới → thêm block sheet đầy đủ

### 5. Cập nhật `CORE_ARCHITECTURE.md` (nếu có API change)
- Thêm action mới vào mục Action Naming Convention
- Format: `{module}.{verb}` theo convention đã có

### 6. Cập nhật `TRACK_ROADMAP.md`
- Nếu tính năng đã hoàn thành → đánh dấu `[x]` hoặc cập nhật status
- Nếu tính năng mới → thêm vào đúng Phase backlog

### 7. Ghi Changelog
Chạy workflow `update-changelog` để ghi nhận tính năng vào `TRACK_CHANGELOG.md`.

### 8. Verify Consistency
Sau khi cập nhật, kiểm tra:
- [ ] Tên module nhất quán giữa `CORE_FEATURES` ↔ `MODULE_*.md` ↔ `CHANGELOG`
- [ ] Không có mâu thuẫn business logic giữa các file
- [ ] Schema change được reflect trong cả `CORE_DATABASE.md` lẫn code service tương ứng
