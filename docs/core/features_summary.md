# Tổng hợp Các Tính Năng Dự Án ToolSEO

Tài liệu này tổng hợp toàn bộ các tính năng của ToolSEO Planner dựa trên phân tích ưu tiên và kế hoạch triển khai.

## I. Mức Độ Ưu Tiên & Lộ Trình Phát Triển (Phasing & Priority)

Hệ thống được chia thành 5 giai đoạn phát triển từ MVP đến tương lai (WP Plugin):

| Ưu Tiên | Giai đoạn (Phase) | Danh sách tính năng chính |
|---|---|---|
| **P0 (Critical)** | Core MVP (Phase 1) | App Shell (SPA), Project Management, Keyword Research, Silo Architecture, Content Outline, Basic Dashboard, Template System. |
| **P1 (High)** | Agency Operations (Phase 2) | Technical Audit, On-Page SEO, GEO Checklist, Client Reporting, Báo giá tự động (Quotation), Content Calendar, Activity Log. |
| **P2 (Medium)** | Data & GSC (Phase 3) | GSC Integration, Ranking Tracker, Backlink Tracker, Advanced Dashboard, Competitor Tracker. |
| **P3 (Nice-to-have)** | Polish (Phase 4) | Notes & Annotations, White-Label, Export enhancements, Dark/Light mode. |
| **P5 (Vision)** | WP Integration (Phase 5) | WP Connection, Outline Sync, Webhook Receiver, Sync Calendar. |

---

## II. Các Module Tính Năng Cốt Lõi (14 Modules)

### 1. Project Management (M1)
- Tạo và quản lý nhiều dự án SEO khách hàng (Client/Website).
- Quản lý trạng thái (active/paused...), duplicate project.

### 2. Keyword Research & Mapping (M2)
- Import danh sách keywords hàng loạt từ CSV.
- Tự động gom nhóm (Cluster) và phân loại Intent.
- Gán Keyword vào từng URL cấu trúc, cảnh báo Cannibalization.

### 3. Silo Architecture & URL Structure (M3)
- Giao diện dạng cây trực quan (Tree view, drag-drop order).
- Hoạch định chiến lược Internal Link In/Out, H1/Meta.
- Xuất bản đồ lưu trữ Silo Map dưới dạng ảnh/JSON.

### 4. Content Outline Generator (M4)
- Sinh Outline chuẩn SEO, E-E-A-T hướng dẫn người viết.
- Quản lý quy trình: Draft -> Reviewed -> Approved -> Sent.
- Tự tạo luồng cấu trúc H2/H3 và Keywords phụ đi kèm.

### 5. Technical Audit Checklist (M5)
- Checklist 55+ mục bao phủ Crawlability, Indexability, Schema, PageSpeed...
- Track progress, gán task fix cho team.

### 6. On-Page SEO Checklist (M6)
- Check 30+ items tối ưu On-Page theo từng URL.
- So sánh chuẩn Content / Ảnh / Meta Tag / Links nội bộ.

### 7. GEO Optimization Checklist (M7)
- Chuẩn tối ưu hóa cho AI Search (Generative Engine Optimization).
- Check format Answer-First, Authority, AI Citation Tracking.

### 8. Backlink Tracker (M8)
- Giao diện Pipeline theo dạng Kanban cho Outreach/Link building.
- Quản lý Metrics (DA), Anchor Text, Chi Phí.

### 9. Ranking Tracker (M9)
- Theo dõi thứ hạng Keyword chủ động thông qua GSC Sync / Nhập tay.
- Biểu đồ xu hướng, Traffic Impact, SERP Feature Tracker.

### 10. Google Search Console Integration (M10)
- Kéo Auto-sync API GSC (Clicks, Impressions, CTR, Position).
- Quét Index Status cho từng URL, Check Sitemap report tự động.

### 11. Dashboard / Project Overview (M11)
- Trang tổng quan Tracking KPIs: Traffic, Silo Status, Content Completion, Links, Audit Scores.

### 12. Client Reporting System (M12)
- Trích xuất dữ liệu, Gen Auto Báo Cáo định kỳ dưới dạng Docs (Status) & Slides (Khách hàng).

### 13. Quotation & Pricing Engine (M13)
- Báo giá tự động dựa trên Rate Card ghép với cấu trúc số lượng Silo Page.
- Render báo giá tự động xuất file (Sheet/PDF).

### 14. Gemini AI Assistant (Copilot) (M14)
*AI được thiết kế để support tốc độ, không tự động publish, yêu cầu review trước khi apply.*
- **AI Tự động Classify Intent:** Phân loại keywords intent.
- **AI Đề xuất Clusters & Silo:** Rút ngắn thời gian mường tượng UI map.
- **AI Tạo Outline chuẩn SEO:** Gen cấu trúc outline chuẩn cực nhanh.
- **AI Khai triển LSI & PAA:** Gợi ý Semantic Entity.
- **AI Check Tối Ưu GEO:** Review nội dung chấm điểm GEO.
- **AI Tóm tắt Report & Recommend lỗi Audit.**

---

## III. Các Tính Năng Đề Xuất Bổ Sung

- **Competitor Tracker (Track đối thủ):** Đối chiếu keyword/content gap trực diện.
- **Content Calendar (Lịch làm việc):** Trực quan hóa deadline content, link cấu trúc Silo với workflow.
- **Activity Log:** Chức năng Accountability, log toàn bộ thay đổi hệ thống.
- **Annotation:** Khả năng note lại thông tin ngày update thuật toán trên Ranking chart.

---

## IV. Tầm Nhìn Tương Lai (Phase 5: WP Bridge Plugin)

- **Kiến trúc "Headless":** ToolSEO (GAS) đóng vai trò trung tâm xử lý Planning & Brief, gửi yêu cầu đẩy Outline JSON cấu hình qua môi trường Client Server WP (Execution).
- **WP Bridge Component:** Nhận API từ ToolSEO -> Quản lý hàng chờ -> Render và Sinh Content (thông qua LLM API gắn ở WP) -> Đăng bài -> Webhook báo lại trạng thái / SEO Score về ToolSEO.
- **Single Source of Truth (_config tab):** Quản lý Brand persona, Tông giọng, Cấm từ ngay tại App Quản lý thay vì cấu hình từng client site.
