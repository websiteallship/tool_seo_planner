# MODULE_KEYWORD — Keyword Research & Mapping (M2)

## Logic & Workflow Nghiệp Vụ

### 1. Import Keywords
```
Planner paste CSV (từ Ahrefs/SEMrush) hoặc upload file
  → Frontend parse: header map tự động (keyword, volume, KD, CPC)
  → Preview bảng 10 rows đầu → Confirm
  → Batch write vào keywords sheet, status mặc định = "researching"
  → Thông báo: "Đã import {n} keywords"
```

### 2. AI Classify Intent
```
Planner chọn keywords chưa có intent → "AI Classify Intent"
  → AIService chia thành chunks 50 keywords/prompt
  → Gọi Gemini, nhận [{keyword, intent, confidence, reasoning}]
  → Preview panel: Planner review, sửa từng row nếu cần
  → Confirm → batch update cột intent
```

### 3. AI Cluster Keywords
```
Planner click "AI Suggest Clusters"
  → Gửi toàn bộ keywords + niche → Gemini
  → Nhận [{cluster_name, keywords[], rationale}]
  → Preview → Planner chỉnh tên cluster
  → Apply → update cluster_group column
```

### 4. Map Keyword → URL (Silo)
```
Planner mở Mapping Panel
  → Chọn keyword → chọn URL từ dropdown (lấy từ silo_structure)
  → Save → update: silo_id, target_url
  → Status auto: "researching" → "mapped"
```

### 5. Cannibalization Detection (tự động)
```
Sau mỗi lần update mapping:
  → Group keywords by target_url
  → Flag nếu 2+ keywords có cùng target_url + cùng intent
  → Highlight màu đỏ + tooltip cảnh báo
```

### Quy Tắc Nghiệp Vụ
- 1 URL chỉ có 1 primary keyword (khác intent = OK, cùng intent = cannibalization)
- Không xóa keyword đã map vào silo → chỉ "paused"
- Status flow: `researching` → `mapped` → `targeting` → `ranking`

---

## Database — Sheet: `keywords`

| Column | Type | Notes |
|---|---|---|
| keyword_id | UUID | |
| keyword | STRING | |
| search_volume | NUMBER | |
| difficulty | NUMBER | 0-100 |
| cpc | NUMBER | USD |
| intent | ENUM | informational/commercial/transactional/navigational |
| cluster_group | STRING | |
| silo_id | STRING | FK → silo_structure |
| target_url | STRING | |
| current_position | NUMBER | Auto-fill từ GSC |
| priority | ENUM | critical/high/medium/low |
| status | ENUM | researching/mapped/targeting/ranking/paused |
| lsi_keywords | TEXT | comma-separated |
| source | STRING | Ahrefs/SEMrush/manual |
| notes | TEXT | |

---

## UI Features

- **Bulk import**: paste CSV textarea hoặc file upload
- **Smart table**: sort multi-column, filter, fulltext search, 50/page
- **Cluster view**: group by cluster_group (collapsible)
- **Intent badges**: màu sắc theo intent (blue/orange/green/gray)
- **Mapping panel**: right sidebar dropdown URL từ silo
- **Cannibalization**: highlighted rows + warning icon
- **Stats cards**: Total / Mapped % / Avg KD / Intent breakdown (donut chart)
- **Bulk actions**: chọn nhiều row → đổi intent/cluster/status
- **Export CSV**: download filtered data

---

## Service & AI API

```javascript
KeywordService.getAll(projectId)
KeywordService.bulkImport(projectId, rows[])
KeywordService.mapToSilo(projectId, keywordId, siloId)
KeywordService.detectCannibalization(projectId)

AIService.classifyIntentBatch(keywords[])
// → [{keyword, intent, confidence, reasoning}]

AIService.suggestClusters(keywords[], niche)
// → [{cluster_name, keywords[], rationale}]
```
