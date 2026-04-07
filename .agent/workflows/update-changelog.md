---
description: Update TRACK_CHANGELOG.md khi release version mới hoặc kết thúc sprint
---

# Workflow: Update Changelog

> Áp dụng sau khi hoàn thành 1 batch tính năng, fix bug, hoặc chuẩn bị release.

## Rules Cần Đọc Trước
- `.agent/rules/01_project_overview.md` — version convention
- `.agent/rules/14_workflow.md` — module naming

## Các Bước

### 1. Xác định loại thay đổi
Phân loại mỗi thay đổi vào đúng nhóm:
- `Added` — tính năng mới hoàn toàn
- `Changed` — thay đổi behavior existing feature
- `Fixed` — bug fix
- `Removed` — xóa / deprecate

### 2. Xác định version mới
Theo Semantic Versioning `vMAJOR.MINOR.PATCH`:
- `PATCH` (+0.0.1): bug fix, text/ui tweak nhỏ
- `MINOR` (+0.1.0): thêm tính năng mới backward-compatible
- `MAJOR` (+1.0.0): breaking change hoặc milestone lớn

### 3. Cập nhật `[Unreleased]` → version cụ thể
Mở `docs/TRACK_CHANGELOG.md`:
1. Rename block `## [Unreleased]` → `## vX.X.X — YYYY-MM-DD`
2. Điền ngày thực tế (ISO format)
3. Thêm block `## [Unreleased]` mới ở trên cùng cho sprint tiếp theo

### 4. Ghi nội dung changelog entry
```markdown
## vX.X.X — YYYY-MM-DD

### Added
- [Module Mx] Mô tả tính năng mới (ngắn gọn, action-oriented)

### Changed
- [Module Mx] Mô tả thay đổi

### Fixed
- [Module Mx] Bug đã fix

### Removed
- [Module Mx] Thứ gì đã bị xóa
```

**Quy tắc viết entry:**
- Prefix bằng `[Module]` nếu thay đổi thuộc module cụ thể (M1–M14)
- 1 dòng = 1 thay đổi độc lập
- Dùng tiếng Việt cho nội dung changelog (không phải UI)
- Không dùng emoji — dùng text thuần

### 5. Cập nhật `TRACK_ROADMAP.md`
- Đánh dấu các items đã hoàn thành trong roadmap
- Cập nhật trạng thái phase nếu cần

### 6. Verify
- Đảm bảo `[Unreleased]` block mới ở đầu file
- Đảm bảo version mới ngay dưới `[Unreleased]`
- File không có duplicate version numbers
