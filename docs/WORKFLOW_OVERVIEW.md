# WORKFLOW_OVERVIEW — Luồng Nghiệp Vụ Tổng Thể ToolSEO

> Mô tả hành trình từ khi nhận khách hàng → triển khai SEO → báo cáo & báo giá.

---

## Sơ Đồ Luồng Chính

```
[Nhận KH mới]
     ↓
[M1] Tạo Project → Auto-tạo Google Sheet từ template
     ↓
[M2] Keyword Research → Import CSV → AI Classify Intent → Cluster
     ↓
[M3] Silo Architecture → Tree view → Map Keywords vào URLs
     ↓
[M4] Content Outline → AI Generate → Review → Export cho writer
     ↓
[M5+M6+M7] Audit → Technical + On-Page + GEO Checklist
     ↓
[M10] GSC Integration → Auto-sync data hàng ngày
     ↓
[M9] Ranking Tracker → Monitor thứ hạng
     ↓
[M8] Backlink Tracker → Pipeline outreach
     ↓
[M11] Dashboard → KPI overview
     ↓
[M12] Client Report → Google Slides/Docs auto-gen (hàng tháng)
     ↓
[M13] Quotation → Smart pricing → Export PDF báo giá
```

> **M14 (AI Copilot)**: Không phải bước tuần tự — AI tích hợp xuyên suốt vào M2 (classify intent, cluster), M3 (suggest silo), M4 (generate outline), M5 (summarize audit), M7 (GEO score), M12 (executive summary).

---

## Workflow Chi Tiết Từng Bước

### Bước 1: Onboard Dự Án (M1)

```
Planner điền thông tin KH
    → System tạo project_id (UUID)
    → Copy template Google Sheet → tạo sheet mới cho KH
    → Lưu spreadsheet_id vào _projects (Master Sheet)
    → Load trang Dashboard trống cho project mới
```

**Kết quả**: Project sẵn sàng, sheet đã có template sẵn cho 13 tabs.

---

### Bước 2: Keyword Research (M2)

```
[Import Phase]
Planner paste CSV từ Ahrefs/SEMrush
    → Parse CSV → Preview bảng
    → Confirm → Batch write vào keywords sheet

[AI Classify Phase]
Planner chọn keywords chưa có intent
    → Click "AI Classify Intent"
    → AIService batch gửi Gemini → nhận {keyword, intent, confidence}[]
    → Preview kết quả → Planner review từng row
    → Confirm → Update keywords sheet

[Cluster Phase]
Planner click "AI Suggest Clusters"
    → AIService nhận toàn bộ keywords → Gemini trả cluster groups
    → Preview → Planner chỉnh sửa nếu cần
    → Apply → Update cluster_group column
```

**Kết quả**: Danh sách keywords đã phân loại intent + grouped by cluster.

---

### Bước 3: Silo Architecture (M3)

```
[Build Silo]
Planner click "AI Suggest Silo" (optional)
    → AIService gửi clusters → Gemini trả silo hierarchy JSON
    → Preview tree structure → Planner chỉnh
    → Hoặc: Planner tự build thủ công (drag-drop tree view)

[Map Keywords]
Với mỗi silo node: Planner gán primary_keyword
    → keywords.silo_id được update (FK link)
    → Map secondary_keywords, H1, meta title/desc

[Internal Links]
Planner chỉ định internal_links_out / in cho từng page
    → Matrix view hiển thị link graph
```

**Kết quả**: Silo hierarchy hoàn chỉnh với URL structure, keywords đã map, meta data.

---

### Bước 4: Content Outline (M4)

```
[Batch Create]
Planner chọn silo nodes cần outline
    → Click "Generate Outlines"
    → OutlineService auto-populate từ silo data (keyword, intent, meta)
    → AI Generate: Gemini tạo heading structure + points cho từng outline
    → Batch save vào content_outlines sheet

[Review & Export]
Planner review từng outline (Draft)
    → Chỉnh sửa inline → Mark "Reviewed"
    → Approve → Mark "Approved"
    → Export dạng Markdown → Gửi cho writer (status: "sent_to_writer")
```

**Kết quả**: Content brief hoàn chỉnh, ready cho team content hoặc AI writing tool.

---

### Bước 5: Technical & On-Page Audit (M5 + M6)

```
[Technical Audit]
Planner click "Load Audit Template"
    → AuditService import 55 checklist items từ template JSON
    → Planner chạy từng mục: check website → điền status + findings
    → AI: "Summarize Audit" → tóm tắt findings + top fixes
    → Assign items cho dev/SEO team với due date

[On-Page Check]
Planner chọn URL (từ silo)
    → Load 30 on-page checklist items
    → Check từng mục → điền current_value / status
    → Score tự động tính (pass count / total)
```

**Kết quả**: Audit report đầy đủ, rõ severity, assign task cho team.

---

### Bước 6: GEO Checklist (M7)

```
Planner chọn URL quan trọng (pillar pages)
    → Load 25 GEO checklist items
    → AI: "Score GEO" → Gemini đánh giá content → trả score + improvements
    → Planner log AI visibility checks (kiểm tra ChatGPT / Gemini / Perplexity)
    → Update status từng mục
```

**Kết quả**: GEO readiness score per page, danh sách cải thiện cụ thể.

---

### Bước 7: GSC Integration & Tracking (M10 + M9)

```
[Setup - 1 lần]
Planner nhập gsc_site_url trong project settings
    → GSCService dùng OAuth tự động của GAS (ScriptApp.getOAuthToken())
    → Test connection → Link confirmed

[Daily Auto-sync]
GAS Time-based trigger: 2:00 AM hàng ngày
    → GSCService.syncPerformance() → pull 3 ngày data từ GSC API
    → Batch upsert vào gsc_performance sheet
    → Enrichment: update keywords.current_position
    → Compute positional changes → update rankings sheet

[Dashboard Update]
    → DashboardService aggregate KPIs từ gsc_performance
    → UI tự refresh khi user load Dashboard
```

**Kết quả**: Data được cập nhật tự động, không cần thao tác thủ công hàng ngày.

---

### Bước 8: Backlink Tracking (M8)

```
Planner import backlinks từ Ahrefs CSV
    → Parse → batch write vào backlinks sheet

Pipeline workflow:
prospect → [Planner contact] → contacted
    → [Negotiating] → negotiating
    → [Link live] → live
    → [Check lại] → lost (nếu bị mất)

Planner update status khi có thay đổi
    → Stats tự động tính: total live, avg DA, dofollow ratio
```

---

### Bước 9: Monthly Reporting (M12)

```
Cuối tháng: Planner click "Generate Monthly Report"
    → ReportService kéo data từ: gsc_performance, rankings, silo, audit, backlinks
    → Copy template Google Slides từ Drive
    → Merge data vào placeholders ({{total_clicks}}, {{avg_position}}...)
    → AI: generate "Executive Summary" narrative
    → Tạo file Slides mới trong client folder trên Drive
    → Planner review → Download / Share link cho KH
```

**Kết quả**: Báo cáo chuyên nghiệp trong vài phút thay vì vài giờ.

---

### Bước 10: Quotation (M13)

```
Planner vào Quotation module
    → System đọc silo_structure (đếm số pages theo type)
    → Match với rate_card (đơn giá per page type)
    → Auto-calculate: pillar pages × giá pillar + cluster × giá cluster...
    → Render bảng báo giá chi tiết
    → Planner điều chỉnh nếu cần
    → Export Google Sheet / PDF → Gửi KH
```

**Kết quả**: Báo giá chính xác, nhất quán, tự động theo scope thực tế.

---

## Luồng AI Copilot (M14)

AI chỉ **suggest, không tự apply**. Flow chuẩn:

```
1. Planner trigger AI action
2. AI generate → kết quả hiển thị trong preview panel
3. Planner review, chỉnh sửa nếu cần
4. Planner confirm → system apply vào database
```

Không có bước nào AI tự ghi vào sheet mà không có human confirmation.

---

## Luồng Phụ: Project Chuyển Giao

```
Project status: active → completed
    → Planner export deliverables package:
        - Silo map (JSON/PDF)
        - Outlines (ZIP Markdown)
        - Audit report (PDF)
        - Ranking history (CSV)
        - Final client report (Slides)
    → Archive project (status: archived)
    → Sheet vẫn giữ nguyên trên Drive (không xóa)
```

---

## Phase 5 Vision: WP Bridge Flow

```
ToolSEO Approve Outline
    → OutlineService triggers WP Bridge webhook (POST brief payload)
    → WP Plugin nhận outline JSON
    → Queue: LLM API viết bài
    → WordPress publish
    → Webhook báo về ToolSEO:
        {status: published, url, word_count, seo_score}
    → ToolSEO update silo_structure.status = 'published'
```
