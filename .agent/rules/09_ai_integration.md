# Rule: AI Integration — Gemini Only

> **Chỉ dùng Gemini API** (`gemini-2.0-flash`). KHÔNG tích hợp OpenAI, Claude, Mistral, hay bất kỳ AI provider nào khác.
> Tất cả AI output cần **human review trước khi apply** — KHÔNG auto-publish, KHÔNG auto-write database.

## AI Use Cases & Prompt Templates

### 1. Classify Keyword Intent
**Output**: `[{keyword, intent, confidence, reasoning}]`
```
Phân loại search intent cho các keywords SEO sau.
Intent options: informational, commercial, transactional, navigational

Keywords: {keywords_json}

Trả về JSON array: [{keyword, intent, confidence (0-1), reasoning}]
```

### 2. Suggest Cluster Groups
**Output**: `[{cluster_name, keywords[], rationale}]`
```
Bạn là SEO expert. Gom nhóm các keywords sau thành topic clusters
phù hợp với website về {niche}.
Keywords: {keywords_json}
Trả về JSON: [{cluster_name, keywords[], rationale}]
Tối đa {max_clusters} clusters, phân nhóm theo intent + topical relevance.
```

### 3. Suggest Silo Structure
**Output**: Silo hierarchy JSON
```
Thiết kế Silo Architecture cho website {domain} ngành {niche}, thị trường {target_market}.
Topic clusters: {clusters_json}
Trả về JSON: [{type: pillar|cluster|support, name, url_slug, primary_keyword, children: [...recursive]}]
```

### 4. Generate Content Outline
**Output**: Structured outline JSON
```
Tạo content outline chuẩn SEO, E-E-A-T cho trang:
- Primary keyword: {keyword}
- Search intent: {intent}
- Loại trang: {content_type}
- Word count target: {word_count}
- LSI keywords: {lsi_keywords}
- Competitor URLs: {competitor_urls}

Trả về JSON:
{h1, meta_title (≤60 chars), meta_description (≤155 chars),
 sections: [{level: H2|H3, heading, points[], keywords[]}],
 internal_link_suggestions: string[], schema_types: string[], eeat_notes: string}
```

### 5. Expand LSI & PAA
**Output**: `{lsi_keywords[], paa_questions[], semantic_entities[]}`
```
Với primary keyword "{keyword}", hãy:
1. Gợi ý 15-20 LSI keywords và semantic variations
2. Liệt kê 10 câu hỏi People-Also-Ask phổ biến nhất
3. Xác định 5-8 semantic entities quan trọng
Trả về JSON: {lsi_keywords[], paa_questions[], semantic_entities[]}
```

### 6. GEO Score Content
**Output**: `{total_score, dimensions, strengths[], improvements[], critical_fixes[]}`
```
Đánh giá nội dung sau theo tiêu chí GEO (Generative Engine Optimization):
Nội dung: {content_or_outline}
Chấm điểm (0-10):
1. Answer-First Format (direct answer trong 100 words đầu)
2. Content Structure (modular, question headings, lists)
3. Authority Signals (author bio, citations, brand mentions)
4. Fact Density (số liệu, data, stats)
Trả về JSON: {total_score, dimensions: {answer_first, structure, authority, fact_density},
 strengths[], improvements[], critical_fixes[]}
```

### 7. Summarize Audit Findings
**Output**: `{executive_summary, critical_count, high_count, top_fixes[], quick_wins[]}`
```
Tóm tắt kết quả Technical SEO Audit cho website {domain}:
Các vấn đề phát hiện: {failed_items_json}
Trả về JSON: {executive_summary (3-5 câu), critical_count, high_count,
 top_fixes: [{issue, recommendation, priority, estimated_effort}], quick_wins[]}
```

## AI Rules

1. **Output luôn là JSON** — parse trong GeminiClient.gs
2. **Batch keywords** 50/prompt — KHÔNG gọi 1 keyword/call
3. **Cache kết quả classify**: CacheService TTL = 3600s
4. **KHÔNG gọi Gemini trong scheduled triggers** — chỉ khi user trigger thủ công
5. **Review-before-apply** là bắt buộc — showAIPreviewModal trước khi save
6. **callGeminiSafe()** — luôn dùng safe wrapper, handle null gracefully

## Error Handling
```javascript
function callGeminiSafe(prompt, schema = null) {
  try { return callGemini(prompt, schema); }
  catch (e) {
    Logger.log('Gemini error: ' + e.message);
    return null; // Alpine.js xử lý null: show toast danger
  }
}
// API.gs xử lý null từ Gemini:
// if (!result) return { success: false, error: 'AI tạm thời không khả dụng. Thử lại sau.' };
```
