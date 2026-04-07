# Rule: Workflow Overview — Công Việc Thực Tế

> Đây là luồng công việc thực tế của SEO Planner khi dùng ToolSEO — không phải data flow kỹ thuật.

## Workflow Tổng Quát

```
Nhận dự án mới
  → Tạo Project (M1) → Sheet tự động được tạo
  → Import Keywords (M2) → AI Classify Intent
  → Gom Cluster → Build Silo Architecture (M3)
  → AI Suggest & Refine Silo
  → Generate Content Outlines (M4)
  → Chạy Technical Audit (M5) + On-Page (M6) + GEO (M7)
  → Connect GSC (M10) → Sync data
  → Track Rankings (M9) + Backlinks (M8)
  → Dashboard (M11) → Monthly Report (M12)
  → Quotation (M13) → Gửi báo giá
```

## Workflow Chi Tiết Theo Module

### New Project Flow (M1)
```
1. Click "New Project" → Điền form: client_name, domain, niche, target_market, gsc_site_url
2. ProjectService.create() → SpreadsheetApp.create() (copy từ PROJECT_TEMPLATE_ID)
3. spreadsheet_id lưu vào _projects
4. User redirect vào Keywords page của project mới
```

### Keyword Import → Classify → Map (M2)
```
1. Import CSV: paste hoặc upload → preview → confirm → KeywordService.bulkImport()
2. AI Classify: chọn all / unclassified → callServer('keyword.classify')
   → showAIPreviewModal → user review → applyAIPreview()
3. Cluster grouping: AI Suggest Clusters hoặc manual
4. Map to URL: mở silo view → drag keyword vào page node → update silo_id
5. Cannibalization flag: tự động highlight (2+ keywords → cùng target_url + intent)
```

### Silo Build (M3)
```
1. Add pillar pages (level=1)
2. Add cluster pages dưới pillar (level=2, parent_id=pillar_id)
3. Add support pages (level=3)
4. AI Suggest: gợi ý structure dựa trên clusters → import vào sheet
5. Set URL slugs, meta title, meta description per node
6. Internal link matrix: tag links in/out
```

### Content Outline Generation (M4)
```
1. Select silo node → "Generate Outline"
2. OutlineService.generate(siloId) → lấy keyword, intent, competitors → Gemini
3. Preview outline → user chỉnh sửa visual editor
4. Set status: draft → reviewed → approved
5. Export: Markdown / Google Doc
6. Batch: select multiple silo nodes → "Generate All"
```

### Audit Workflow (M5, M6, M7)
```
M5 Technical Audit:
  1. Load template (55 items) → AuditService.loadTemplate()
  2. Planner review từng item: pass / fail / na
  3. Điền details + recommendation cho fail items
  4. AI Summarize findings → executive summary
  5. Re-audit: snapshot kết quả → reset status

M6 On-Page: per-URL checklist tự động từ silo
M7 GEO: per-URL GEO score → AI chấm điểm
```

### GSC Sync Workflow (M10)
```
Manual sync:
  1. Click "Sync GSC Now" → callServer('gsc.syncPerformance')
  2. GSCService → GSCClient.querySearchAnalytics() → batchAddRows vào gsc_performance
  3. Enrichment: update keywords.current_position từ GSC data
  4. Dashboard KPIs refresh

Auto sync (Trigger 2AM daily):
  1. dailySync() → lấy tất cả active projects với gsc_sync_enabled=true
  2. GSCService.syncPerformance() per project (với LockService)
  3. Logger.log kết quả
```

### Monthly Report (M12)
```
1. Click "Generate Monthly Report"
2. ReportService.generateSlides(projectId, month)
3. Lấy data từ: gsc_performance, keywords, silo_structure, rankings, technical_audit
4. createSlidesFromTemplate() → replace {{placeholders}}
5. AI narrative: tóm tắt insights text
6. Drive URL lưu vào _reports sheet
7. History list: hiển thị tất cả reports đã tạo
```

### Quotation (M13)
```
1. Rate card: setup đơn giá per hạng mục (service + expert tier)
2. Auto-calculate: đếm số pages per silo_type × unit_price
3. Preview báo giá: breakdown table
4. Save version → export Google Sheet
5. Lịch sử: per-project quotation history
```

## Action Naming Convention (API.gs)

Format: `{module}.{verb}`

```
project.getAll / project.create / project.update / project.archive
keyword.getAll / keyword.add / keyword.bulkImport / keyword.classify / keyword.applyIntents / keyword.map / keyword.delete
silo.getTree / silo.addNode / silo.updateNode / silo.moveNode / silo.delete / silo.suggestAI
outline.getAll / outline.generate / outline.generateBatch / outline.update / outline.exportMd / outline.exportDoc
audit.loadTemplate / audit.getAll / audit.updateItem / audit.summarizeAI / audit.snapshot
onpage.generateForSilo / onpage.updateItem
geo.getAll / geo.updateItem / geo.scoreAI
gsc.syncPerformance / gsc.inspectUrl / gsc.checkSitemap / gsc.testConnection
ranking.getAll / ranking.import / ranking.snapshot
backlink.getAll / backlink.import / backlink.updateStatus
dashboard.getKPIs / dashboard.getChartData
report.generate / report.getHistory
quotation.getRateCard / quotation.saveRateCard / quotation.calculate / quotation.save
ai.classifyIntent / ai.suggestClusters / ai.suggestSilo / ai.generateOutline / ai.expandLSI / ai.scoreGEO / ai.summarizeAudit
```
