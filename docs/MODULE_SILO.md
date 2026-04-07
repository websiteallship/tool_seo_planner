# MODULE_SILO — Silo Architecture & URL Structure (M3)

## Logic & Workflow Nghiệp Vụ

### 1. Xây Dựng Silo Structure
```
[Cách 1 — AI Suggest]
Planner click "AI Suggest Silo"
  → Gửi topic clusters + niche + target_market → Gemini
  → Nhận hierarchy JSON: [{type, name, url_slug, primary_keyword, children[]}]
  → Preview dạng tree → Planner chỉnh sửa
  → Import → batch write vào silo_structure sheet

[Cách 2 — Manual Build]
Planner click "+ Add Pillar Page"
  → Nhập: silo_name, url_slug, primary_keyword
  → Lưu → hiển thị trong tree
  → Click node → "+ Add Child" để thêm cluster/support pages
```

### 2. Kéo Thả Reorder / Thay Đổi Parent
```
Planner drag node lên/xuống hoặc sang node khác
  → Nếu drag vào node khác: parent_id thay đổi, level recalculate
  → Nếu drag trong cùng parent: order thay đổi
  → Auto save sau 500ms debounce
```

### 3. Map Metadata Per Page
```
Planner click node trong tree → right panel mở
  → Edit: h1_tag, meta_title, meta_description
  → Internal links: chọn URLs đi/đến từ dropdown (cùng silo)
  → Schema types: chọn checkboxes
  → Save → update row trong sheet
```

### 4. Internal Link Matrix
```
Planner click tab "Link Matrix"
  → Render table N×N (pages cross-reference)
  → Ô màu xanh = có link, click để add/remove
  → Auto-update internal_links_in / internal_links_out tương ứng
```

### 5. Export Silo Map
```
Planner click "Export Map"
  → Hỏi format: JSON / Markdown tree / Mermaid diagram
  → Generate → Download hoặc Google Doc
```

### Quy Tắc Nghiệp Vụ
- Level hierarchy: 0=Homepage, 1=Pillar, 2=Cluster, 3=Support
- Pillar page phải có internal link về Homepage
- Support page phải link về Cluster page cha
- Không xóa node có child → phải remove children trước hoặc reassign
- URL slug: lowercase, không dấu, dấu `-` thay space

---

## Database — Sheet: `silo_structure`

| Column | Type | Notes |
|---|---|---|
| silo_id | UUID | |
| silo_name | STRING | |
| silo_type | ENUM | homepage/pillar/cluster/support/blog_category/blog_post |
| parent_id | STRING | null nếu root (homepage) |
| level | NUMBER | 0/1/2/3 |
| target_url | STRING | URL slug |
| primary_keyword | STRING | |
| secondary_keywords | TEXT | comma-sep |
| search_intent | ENUM | informational/commercial/transactional |
| h1_tag | STRING | |
| meta_title | STRING | ≤60 chars |
| meta_description | TEXT | ≤155 chars |
| internal_links_out | TEXT | URLs nối đi (pipe-sep) |
| internal_links_in | TEXT | URLs nối vào (pipe-sep) |
| word_count_target | NUMBER | |
| content_type | ENUM | service/category/product/guide/blog/landing |
| schema_types | TEXT | |
| status | ENUM | planned/approved/content_brief_sent/published/optimizing |
| order | NUMBER | Sort order trong cùng parent |
| notes | TEXT | |

---

## UI Features

- **Tree view**: hierarchy trực quan, indent, collapse/expand all
- **Drag-drop**: kéo thả reorder và thay đổi parent
- **Quick add**: thêm child node từ context menu
- **Edit panel**: right sidebar khi click node
- **Link matrix**: table cross-reference internal links
- **Bulk edit**: meta title/description nhiều pages cùng lúc
- **Export**: JSON / Markdown / Mermaid / Google Doc
- **Stats**: Total pages / By type / By status (horizontal bar chart)
- **Index badges**: ✅/❌ per page (từ GSC URL Inspection)

---

## Service & AI API

```javascript
SiloService.getTree(projectId)
// → Nested hierarchy [{...node, children: [...]}]

SiloService.addNode(projectId, data, parentId)
SiloService.moveNode(projectId, nodeId, newParentId, newOrder)
SiloService.updateMetadata(projectId, siloId, data)
SiloService.getLinkMatrix(projectId)
SiloService.exportMap(projectId, format)

AIService.suggestSilo(clusters[], niche, targetMarket)
// → [{type, name, url_slug, primary_keyword, children[]}]
```
