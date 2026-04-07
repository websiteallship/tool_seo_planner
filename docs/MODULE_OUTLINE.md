# MODULE_OUTLINE — Content Outline Generator (M4)

> Module này KHÔNG viết content. Chỉ tạo brief/outline chuẩn SEO để export cho team content hoặc AI writing tool.

## Logic & Workflow Nghiệp Vụ

### 1. Tạo Outline (AI Generate)
```
Planner chọn silo node(s) cần tạo outline
  → Click "Generate Outline"
  → OutlineService.generate(siloId):
      Pull data từ silo_structure: keyword, intent, meta, wordcount
      Pull LSI keywords từ keywords sheet
      Build prompt → Gemini generate heading structure
      Nhận JSON: {h1, meta_title, meta_description, sections[], schema_types[], eeat_notes}
  → Tạo record trong content_outlines sheet, status = "draft"
  → Preview outline dạng visual editor
```

### 2. Review & Edit Outline
```
Planner click vào outline → Edit view mở
  → Drag-drop để sắp xếp lại sections
  → Edit inline: heading text, key points, keywords
  → Add/remove H2/H3 sections
  → Mark "Reviewed" khi hài lòng → status: "draft" → "reviewed"
```

### 3. Approve & Export
```
Planner review lần cuối → click "Approve"
  → status: "reviewed" → "approved"
  → Export options:
      Markdown (.md): format heading, bullet points — cho writer hoặc XANH AI
      Google Doc: auto-create file trong Drive client folder
      JSON: raw structure cho integration
  → status update: "approved" → "sent_to_writer"
  → Ghi nhận: assigned_to field
```

### 4. Batch Create
```
Planner chọn nhiều silo nodes → "Batch Generate Outlines"
  → Loop qua từng node, gọi AI generate
  → Chunked: 5 outlines xử lý song song (tránh timeout)
  → Progress bar: "Đang tạo 3/10 outlines..."
  → Tất cả lưu status = "draft" — cần review riêng lẻ
```

### Quy Tắc Nghiệp Vụ
- Mỗi silo node chỉ có 1 outline active (draft/reviewed/approved)
- Không thể "sent_to_writer" nếu chưa "approved"
- Completed outline: writer confirm đã nhận → status "completed"
- Outline structure lưu dạng JSON (flexible, không fixed schema)

---

## Database — Sheet: `content_outlines`

| Column | Type | Notes |
|---|---|---|
| outline_id | UUID | |
| silo_id | STRING | FK → silo_structure |
| target_url | STRING | |
| working_title | STRING | |
| primary_keyword | STRING | |
| secondary_keywords | TEXT | |
| search_intent | STRING | |
| target_word_count | NUMBER | |
| meta_title | STRING | ≤60 chars |
| meta_description | STRING | ≤155 chars |
| h1_tag | STRING | |
| outline_structure | JSON | `[{level, heading, points[], keywords[]}]` |
| internal_links | TEXT | URLs cần link đến |
| external_links | TEXT | Sources cần cite |
| schema_required | TEXT | |
| eeat_notes | TEXT | |
| geo_requirements | TEXT | |
| tone | STRING | |
| competitor_urls | TEXT | |
| status | ENUM | draft/reviewed/approved/sent_to_writer/completed |
| assigned_to | STRING | |
| notes | TEXT | |

**outline_structure JSON format:**
```json
[
  {"level": "H2", "heading": "Section Title", "points": ["point 1", "point 2"], "keywords": ["kw1"]},
  {"level": "H3", "heading": "Sub-section", "points": ["detail"], "keywords": ["kw2"]}
]
```

---

## UI Features

- **Outline builder**: visual drag-drop heading hierarchy editor
- **Auto-populate**: kéo data từ silo khi tạo mới
- **Preview mode**: render outline dạng readable document
- **Status workflow**: badges + filter theo status
- **Export**: Markdown / JSON / Google Doc (auto-create)
- **Batch create**: multi-select silo nodes
- **Template system**: load từ outline template có sẵn
- **Word count tracker**: estimate từ outline structure

---

## Service & AI API

```javascript
OutlineService.getAll(projectId)
OutlineService.generate(projectId, siloId)
OutlineService.batchGenerate(projectId, siloIds[])
OutlineService.update(projectId, outlineId, data)
OutlineService.updateStatus(projectId, outlineId, status)
OutlineService.exportMarkdown(projectId, outlineId)
OutlineService.exportToGoogleDoc(projectId, outlineId)

AIService.generateOutline(siloData, keywordData)
// → {h1, meta_title, meta_description, sections[], schema_types[], eeat_notes}
```
