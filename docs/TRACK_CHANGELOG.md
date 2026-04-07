# TRACK_CHANGELOG — Lịch Sử Thay Đổi

> Ghi nhận tất cả thay đổi theo version. Format: [YYYY-MM-DD] vX.X.X

---

## [Unreleased]

- Keyword Research module (M2)
- Silo Architecture module (M3)

---

## v0.3.0 — 2026-04-07

### Added

- [M1] Thêm chức năng quản lý danh sách Project (`projects.html`), bao gồm các tính năng: xem danh sách, lọc/tìm kiếm, thêm mới, sửa, nhân bản (duplicate), lưu trữ (archive), và xóa.
- [Shell] Thêm responsive UI cho mobile (sidebar overlay, auto-close sidebar, tối ưu bảng dữ liệu, vả responsive flexbox).

---

## v0.2.1 — 2026-04-07

### Added
- [Core] `SetupService.gs` hỗ trợ tạo và sử dụng Google Drive Folder làm trung tâm lưu trữ thay cho việc dùng 1 Master Sheet ID duy nhất. Tự động khởi tạo file `_ToolSEO_Master` (chứa tab `_projects`) bên trong folder.
- [M1] `ProjectService.gs` và `SeedService.gs` giờ đây tự động lưu file spreadsheet mới của dự án vào đúng Google Drive Folder đã cấu hình.
- [M1] Settings UI được nâng cấp để nhập và Test Connection với Drive Folder ID thay vì Master Sheet ID.

### Fixed
- Lỗi `SeedService` không map dữ liệu mock data do spreadsheet thiếu tabs. Đã code tự động sinh 12 module tabs và dọn sạch dữ liệu cũ khi click "Seed Mock Data".
- Thay thế hoàn toàn cách lấy Project ID bằng Alpine context thay cho việc truy xuất trực tiếp component `sl-select` vì component trả value không chính xác.

---

## v0.2.0 — 2026-04-05

### Added
- [M1] Hoàn thiện giao diện Settings Page: thêm password-toggle để ẩn hiện keys, thêm nút Test Connection Master Sheet
- [M1] Cấu hình Tailwind CSS cao cấp (Glassmorphism UI, Shadows, Animations) cho topbar, sidebar và bảng biểu
- [Core] `API.gs` dispatcher hoàn thiện cơ chế mapping RPC calls
- [Core] `SetupService.gs` hỗ trợ trả về raw key và logic kết nối check Spreadsheet

### Changed
- Đổi layout SPA shell: xóa thao tác tạo Project ở topbar, chuyển sang modal "No Project Selected"

### Fixed
- Lỗi AlpineJS conflict với Lucide icons khiến menu không hiện icon (chuyển sang render tĩnh HTML)
- Lỗi Tailwind CDN không nhận diện `@apply` bằng cách bổ sung thuộc tính type chuẩn cho tag `<style>`

---

## v0.1.0 — 2026-04-05

### Added
- Khởi tạo cấu trúc dự án
- Toàn bộ tài liệu kỹ thuật trong `docs/`:
  - CORE_PROJECT, CORE_ARCHITECTURE, CORE_DATABASE, CORE_FEATURES, CORE_AI_CONTEXT
  - WORKFLOW_OVERVIEW
  - ARCH_PATTERNS, ARCH_INTEGRATIONS, ARCH_PERFORMANCE
  - GOV_CODING_STANDARDS, GOV_UX_GUIDELINES
  - MODULE_PROJECT, MODULE_KEYWORD, MODULE_SILO, MODULE_OUTLINE
  - MODULE_AUDIT, MODULE_GSC, MODULE_DASHBOARD, MODULE_REPORTING
  - OPS_DEPLOYMENT, OPS_TESTING
  - REF_AI_HANDOVER
  - TRACK_ROADMAP, TRACK_DECISIONS, TRACK_CHANGELOG (file này)
- Skills library: 50 skills trong `.agent/skills/`

---

## Template Entry (Copy khi có version mới)

```
## vX.X.X — YYYY-MM-DD

### Added
- Tính năng mới

### Changed
- Thay đổi existing feature

### Fixed
- Bug fixes

### Removed
- Deprecated features
```
