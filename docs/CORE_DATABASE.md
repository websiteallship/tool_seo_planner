# CORE_DATABASE — Schema Google Sheets

> Mỗi dự án = 1 Google Sheet file riêng (tạo tự động từ template).
> Primary key mọi sheet: `{entity}_id` (UUID string).

---

## Master Data (App-level)

> **Lưu ý Kiến trúc**: Mọi file (bao gồm `_ToolSEO_Master` và các project spreadsheets) đều được tự động lưu trong **Google Drive Folder** do hệ thống cấu hình trong Settings (Settings > Google Drive Folder ID).

### Sheet: `_ToolSEO_Master` (Tab: `_projects`)

| Column | Type | Description |
|---|---|---|
| project_id | STRING | UUID |
| client_name | STRING | Tên khách hàng |
| website_name | STRING | Tên website |
| domain | STRING | Domain chính |
| niche | STRING | Ngành nghề |
| target_market | STRING | Thị trường / Khu vực |
| gsc_site_url | STRING | GSC property URL |
| spreadsheet_id | STRING | Google Sheet ID của project |
| status | ENUM | `active` / `paused` / `completed` / `archived` |
| start_date | DATE | Ngày bắt đầu |
| planner | STRING | SEO Planner phụ trách |
| gsc_sync_enabled | BOOLEAN | Bật/tắt auto-sync GSC |
| gsc_last_sync | DATETIME | Lần sync GSC gần nhất |
| created_at | DATETIME | Ngày tạo project |
| updated_at | DATETIME | Lần update gần nhất |
| notes | TEXT | Ghi chú |

---

## Per-Project Sheet

### Sheet: `_config`

| Column | Type | Description |
|---|---|---|
| key | STRING | Config key |
| value | TEXT | Config value |
| description | STRING | Mô tả |

**Default config keys:**
```
brand_name, target_audience, tone_of_voice, blacklist_words,
gemini_model, report_template_slides_id,
report_template_docs_id, rate_card_currency
```

> **Lưu ý**: `gsc_site_url` chỉ lưu trong `_projects` (để tránh duplicate).

---

### Sheet: `keywords`

| Column | Type | Description |
|---|---|---|
| keyword_id | STRING | UUID |
| keyword | STRING | Từ khóa |
| search_volume | NUMBER | Volume/tháng |
| difficulty | NUMBER | KD (0-100) |
| cpc | NUMBER | CPC ($) |
| intent | ENUM | `informational` / `commercial` / `transactional` / `navigational` |
| cluster_group | STRING | Topic cluster name |
| silo_id | STRING | FK → silo_structure |
| target_url | STRING | URL mục tiêu đã map |
| current_position | NUMBER | Vị trí hiện tại (từ GSC) |
| priority | ENUM | `critical` / `high` / `medium` / `low` |
| status | ENUM | `researching` / `mapped` / `targeting` / `ranking` / `paused` |
| lsi_keywords | TEXT | LSI/semantic keywords (comma-sep) |
| source | STRING | Ahrefs / SEMrush / manual |
| notes | TEXT | Ghi chú |

---

### Sheet: `silo_structure`

| Column | Type | Description |
|---|---|---|
| silo_id | STRING | UUID |
| silo_name | STRING | Tên Silo / Page |
| silo_type | ENUM | `homepage` / `pillar` / `cluster` / `support` / `blog_category` / `blog_post` |
| parent_id | STRING | ID parent (null nếu root) |
| level | NUMBER | Depth: 0=home, 1=pillar, 2=cluster, 3=support |
| target_url | STRING | URL slug |
| primary_keyword | STRING | Keyword chính |
| secondary_keywords | TEXT | Keywords phụ (comma-sep) |
| search_intent | ENUM | `informational` / `commercial` / `transactional` |
| h1_tag | STRING | Proposed H1 |
| meta_title | STRING | Proposed meta title (≤60 chars) |
| meta_description | TEXT | Proposed meta description (≤155 chars) |
| internal_links_out | TEXT | URLs liên kết đi (pipe-sep) |
| internal_links_in | TEXT | URLs liên kết vào (pipe-sep) |
| word_count_target | NUMBER | Target word count |
| content_type | ENUM | `service` / `category` / `product` / `guide` / `blog` / `landing` |
| schema_types | TEXT | Schema markup required |
| status | ENUM | `planned` / `approved` / `content_brief_sent` / `published` / `optimizing` |
| order | NUMBER | Sort order trong cùng parent |
| notes | TEXT | Ghi chú |

---

### Sheet: `content_outlines`

| Column | Type | Description |
|---|---|---|
| outline_id | STRING | UUID |
| silo_id | STRING | FK → silo_structure |
| target_url | STRING | URL mục tiêu |
| working_title | STRING | Tiêu đề dự kiến |
| primary_keyword | STRING | Keyword chính |
| secondary_keywords | TEXT | Keywords phụ |
| search_intent | STRING | Intent |
| target_word_count | NUMBER | Word count mục tiêu |
| meta_title | STRING | Proposed meta title |
| meta_description | STRING | Proposed meta description |
| h1_tag | STRING | Proposed H1 |
| outline_structure | JSON | `[{level, heading, points[], keywords[]}]` |
| internal_links | TEXT | URLs cần link đến |
| external_links | TEXT | Sources cần cite |
| schema_required | TEXT | Schema types |
| eeat_notes | TEXT | E-E-A-T requirements |
| geo_requirements | TEXT | GEO optimization notes |
| tone | STRING | Tone of voice |
| competitor_urls | TEXT | URLs đối thủ tham khảo |
| status | ENUM | `draft` / `reviewed` / `approved` / `sent_to_writer` / `completed` |
| assigned_to | STRING | Writer / AI tool |
| notes | TEXT | Additional brief notes |

---

### Sheet: `technical_audit`

| Column | Type | Description |
|---|---|---|
| audit_id | STRING | UUID |
| category | ENUM | `crawlability` / `indexability` / `performance` / `mobile` / `security` / `schema` / `architecture` |
| item | STRING | Checklist item description |
| severity | ENUM | `critical` / `high` / `medium` / `low` |
| status | ENUM | `not_checked` / `pass` / `fail` / `in_progress` / `fixed` / `na` |
| url | STRING | URL liên quan |
| details | TEXT | Findings chi tiết |
| recommendation | TEXT | Đề xuất fix |
| assigned_to | STRING | Người xử lý |
| due_date | DATE | Deadline |
| fixed_date | DATE | Ngày fix xong |
| evidence | STRING | Link screenshot/URL |
| notes | TEXT | Ghi chú |

---

### Sheet: `onpage_checklist`

| Column | Type | Description |
|---|---|---|
| check_id | STRING | UUID |
| url | STRING | URL trang |
| silo_id | STRING | FK → silo_structure |
| category | ENUM | `title` / `meta` / `headings` / `content` / `images` / `links` / `schema` / `ux` |
| item | STRING | Checklist item |
| status | ENUM | `not_checked` / `pass` / `fail` / `na` |
| current_value | TEXT | Giá trị hiện tại |
| recommended_value | TEXT | Giá trị đề xuất |
| notes | TEXT | Ghi chú |

---

### Sheet: `geo_checklist`

| Column | Type | Description |
|---|---|---|
| geo_id | STRING | UUID |
| url | STRING | URL trang |
| silo_id | STRING | FK → silo_structure |
| category | ENUM | `technical` / `content_structure` / `authority` / `measurement` |
| item | STRING | Checklist item |
| status | ENUM | `not_checked` / `pass` / `fail` / `in_progress` / `na` |
| details | TEXT | Chi tiết findings |
| notes | TEXT | Ghi chú |

---

### Sheet: `backlinks`

| Column | Type | Description |
|---|---|---|
| backlink_id | STRING | UUID |
| referring_domain | STRING | Domain nguồn |
| referring_url | STRING | URL nguồn |
| target_url | STRING | URL đích |
| anchor_text | STRING | Anchor text |
| link_type | ENUM | `dofollow` / `nofollow` / `ugc` / `sponsored` |
| domain_authority | NUMBER | DA/DR |
| status | ENUM | `prospect` / `contacted` / `negotiating` / `live` / `lost` / `rejected` |
| outreach_date | DATE | Ngày liên hệ |
| live_date | DATE | Ngày link live |
| contact_info | STRING | Email / contact |
| cost | NUMBER | Chi phí (VND/USD) |
| campaign | STRING | Tên campaign |
| notes | TEXT | Ghi chú |

---

### Sheet: `rankings`

| Column | Type | Description |
|---|---|---|
| tracking_id | STRING | UUID |
| keyword | STRING | Từ khóa tracked |
| target_url | STRING | URL mục tiêu |
| position | NUMBER | Avg. Position |
| previous_position | NUMBER | Vị trí check trước |
| change | NUMBER | +/- thay đổi |
| best_position | NUMBER | Best ever |
| clicks | NUMBER | Clicks (từ GSC) |
| impressions | NUMBER | Impressions |
| ctr | NUMBER | CTR % |
| device | ENUM | `desktop` / `mobile` |
| country | STRING | Country code |
| tracked_date | DATE | Ngày check |
| data_source | ENUM | `gsc_auto` / `manual` / `csv_import` |
| featured_snippet | BOOLEAN | Có Featured Snippet? |
| ai_overview | BOOLEAN | Có AI Overview? |
| serp_features | TEXT | Các SERP features khác |
| notes | TEXT | Ghi chú |

---

### Sheet: `gsc_performance`

| Column | Type | Description |
|---|---|---|
| record_id | STRING | UUID |
| query | STRING | Search query |
| page | STRING | URL trang |
| clicks | NUMBER | Số click |
| impressions | NUMBER | Số hiển thị |
| ctr | NUMBER | CTR (%) |
| position | NUMBER | Vị trí trung bình |
| country | STRING | Mã quốc gia |
| device | ENUM | `desktop` / `mobile` / `tablet` |
| date | DATE | Ngày |
| synced_at | DATETIME | Thời điểm sync |

---

### Sheet: `gsc_index_status`

| Column | Type | Description |
|---|---|---|
| inspection_id | STRING | UUID |
| url | STRING | URL được inspect |
| verdict | ENUM | `PASS` / `NEUTRAL` / `FAIL` |
| coverage_state | STRING | Trạng thái index |
| robotstxt_state | ENUM | `ALLOWED` / `DISALLOWED` |
| indexing_state | STRING | Indexing status |
| page_fetch_state | STRING | Fetch status |
| crawled_as | ENUM | `DESKTOP` / `MOBILE` |
| canonical_url | STRING | Google-selected canonical |
| user_canonical | STRING | User-declared canonical |
| mobile_usability | STRING | Mobile friendly status |
| mobile_issues | JSON | Danh sách lỗi mobile |
| last_crawl_time | DATETIME | Lần crawl gần nhất |
| inspected_at | DATETIME | Thời điểm inspect |

---

### Sheet: `rate_card`

| Column | Type | Description |
|---|---|---|
| item_id | STRING | UUID |
| category | STRING | Hạng mục dịch vụ |
| item_name | STRING | Tên hạng mục |
| unit | STRING | Đơn vị (page / keyword / hour / month) |
| unit_price | NUMBER | Đơn giá |
| tier | ENUM | `standard` / `expert` |
| currency | ENUM | `VND` / `USD` |
| notes | TEXT | Ghi chú |

---

### Sheet: `quotation`

| Column | Type | Description |
|---|---|---|
| quote_id | STRING | UUID |
| created_date | DATE | Ngày tạo |
| version | NUMBER | Version báo giá |
| status | ENUM | `draft` / `sent` / `approved` / `rejected` |
| total_amount | NUMBER | Tổng giá trị |
| currency | ENUM | `VND` / `USD` |
| breakdown | JSON | Chi tiết theo hạng mục |
| notes | TEXT | Ghi chú |

---

### Sheet: `_reports`

| Column | Type | Description |
|---|---|---|
| report_id | STRING | UUID |
| report_type | ENUM | `monthly_slides` / `status_doc` / `strategy_deck` |
| generated_date | DATE | Ngày xuất |
| period | STRING | Tháng/Quý (vd: "2026-04") |
| drive_url | STRING | Link file Google Drive |
| drive_file_id | STRING | Google Drive file ID |
| status | ENUM | `draft` / `sent_to_client` |
| notes | TEXT | Ghi chú |
