# Rule: Database Schema Reference

> Primary key mọi sheet: `{entity}_id` (UUID). Mỗi project = 1 Google Sheet file riêng.

## Master Sheet: `_projects`

| Column | Type | Notes |
|---|---|---|
| project_id | STRING | UUID |
| client_name | STRING | |
| website_name | STRING | |
| domain | STRING | |
| niche | STRING | |
| target_market | STRING | |
| gsc_site_url | STRING | GSC property URL |
| spreadsheet_id | STRING | Per-project Sheet ID |
| status | ENUM | `active/paused/completed/archived` |
| start_date / created_at / updated_at | DATE/DATETIME | |
| planner | STRING | |
| gsc_sync_enabled | BOOLEAN | |
| gsc_last_sync | DATETIME | |

## Per-Project Sheets

### `_config` — Project Settings
key / value / description (default keys: brand_name, target_audience, tone_of_voice, blacklist_words, gemini_model, report_template_*_id, rate_card_currency)

> `gsc_site_url` CHỈ lưu trong `_projects` (không duplicate vào _config)

### `keywords`
keyword_id · keyword · search_volume · difficulty · cpc · intent(ENUM) · cluster_group · silo_id(FK) · target_url · current_position · priority(ENUM) · status(ENUM) · lsi_keywords · source · notes

**intent ENUM**: `informational / commercial / transactional / navigational`
**priority ENUM**: `critical / high / medium / low`
**status ENUM**: `researching / mapped / targeting / ranking / paused`

### `silo_structure`
silo_id · silo_name · silo_type(ENUM) · parent_id · level(0=home,1=pillar,2=cluster,3=support) · target_url · primary_keyword · secondary_keywords · search_intent · h1_tag · meta_title(≤60) · meta_description(≤155) · internal_links_out · internal_links_in · word_count_target · content_type(ENUM) · schema_types · status(ENUM) · order · notes

**silo_type ENUM**: `homepage / pillar / cluster / support / blog_category / blog_post`
**status ENUM**: `planned / approved / content_brief_sent / published / optimizing`

### `content_outlines`
outline_id · silo_id(FK) · target_url · working_title · primary_keyword · secondary_keywords · search_intent · target_word_count · meta_title · meta_description · h1_tag · outline_structure(JSON) · internal_links · external_links · schema_required · eeat_notes · geo_requirements · tone · competitor_urls · status(ENUM) · assigned_to · notes

**outline_structure format**: `[{level, heading, points[], keywords[]}]`
**status ENUM**: `draft / reviewed / approved / sent_to_writer / completed`

### `technical_audit`
audit_id · category(ENUM) · item · severity(ENUM) · status(ENUM) · url · details · recommendation · assigned_to · due_date · fixed_date · evidence · notes

**category**: `crawlability / indexability / performance / mobile / security / schema / architecture`
**severity**: `critical / high / medium / low`
**status**: `not_checked / pass / fail / in_progress / fixed / na`

### `onpage_checklist`
check_id · url · silo_id(FK) · category(ENUM) · item · status(ENUM) · current_value · recommended_value · notes

**category**: `title / meta / headings / content / images / links / schema / ux`

### `geo_checklist`
geo_id · url · silo_id(FK) · category(ENUM) · item · status(ENUM) · details · notes

**category**: `technical / content_structure / authority / measurement`

### `backlinks`
backlink_id · referring_domain · referring_url · target_url · anchor_text · link_type(ENUM) · domain_authority · status(ENUM) · outreach_date · live_date · contact_info · cost · campaign · notes

**link_type**: `dofollow / nofollow / ugc / sponsored`
**status**: `prospect / contacted / negotiating / live / lost / rejected`

### `rankings`
tracking_id · keyword · target_url · position · previous_position · change · best_position · clicks · impressions · ctr · device(ENUM) · country · tracked_date · data_source(ENUM) · featured_snippet(BOOLEAN) · ai_overview(BOOLEAN) · serp_features · notes

### `gsc_performance`
record_id · query · page · clicks · impressions · ctr · position · country · device(ENUM) · date · synced_at

### `gsc_index_status`
inspection_id · url · verdict(ENUM: PASS/NEUTRAL/FAIL) · coverage_state · robotstxt_state · indexing_state · page_fetch_state · crawled_as · canonical_url · user_canonical · mobile_usability · mobile_issues(JSON) · last_crawl_time · inspected_at

### `rate_card`
item_id · category · item_name · unit · unit_price · tier(ENUM: standard/expert) · currency(ENUM: VND/USD) · notes

### `quotation`
quote_id · created_date · version · status(ENUM: draft/sent/approved/rejected) · total_amount · currency · breakdown(JSON) · notes

### `_reports`
report_id · report_type(ENUM: monthly_slides/status_doc/strategy_deck) · generated_date · period · drive_url · drive_file_id · status(ENUM: draft/sent_to_client) · notes
