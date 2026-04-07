// services/KeywordService.gs — M2: Keyword Research & Mapping
// Quản lý keywords: CRUD, bulk import, AI classify intent, mapping vào silo

const KeywordService = {
  /**
   * Lấy tất cả keywords của 1 project
   */
  getAll(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    return getWithCache(
      cacheKey('keywords', projectId),
      () => SheetDB.getRows(ssId, 'keywords'),
      CACHE_TTL.keywords
    );
  },

  /**
   * Thêm 1 keyword
   */
  add(projectId, data) {
    validateRequired(data, ['keyword']);
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const row = {
      keyword_id: uuid(),
      keyword: data.keyword.trim(),
      search_volume: data.search_volume || '',
      difficulty: data.difficulty || '',
      intent: data.intent || '',
      cluster_group: data.cluster_group || '',
      silo_id: data.silo_id || '',
      target_url: data.target_url || '',
      current_position: data.current_position || '',
      featured_snippet: data.featured_snippet || false,
      notes: data.notes || '',
      created_at: nowISO()
    };
    SheetDB.addRow(ssId, 'keywords', row);
    invalidateCache([cacheKey('keywords', projectId)]);
    return row;
  },

  /**
   * Bulk import từ CSV parse result
   * @param {Object[]} rows — array of { keyword, search_volume?, difficulty?, ... }
   */
  bulkAdd(projectId, rows) {
    validateRequired({ rows }, ['rows']);
    if (!Array.isArray(rows) || rows.length === 0) throw new Error('rows must be a non-empty array');

    const ssId = ProjectService.getSpreadsheetId(projectId);
    const now = nowISO();
    const dataRows = rows.map(r => ({
      keyword_id: uuid(),
      keyword: String(r.keyword || '').trim(),
      search_volume: r.search_volume || '',
      difficulty: r.difficulty || '',
      intent: r.intent || '',
      cluster_group: r.cluster_group || '',
      silo_id: '',
      target_url: '',
      current_position: '',
      featured_snippet: false,
      notes: '',
      created_at: now
    })).filter(r => r.keyword); // Bỏ qua rows không có keyword

    SheetDB.batchAddRows(ssId, 'keywords', dataRows);
    invalidateCache([cacheKey('keywords', projectId)]);
    Logger.log(`[KeywordService] INFO: Bulk added ${dataRows.length} keywords to project ${projectId}`);
    return { imported: dataRows.length };
  },

  /**
   * Cập nhật keyword
   */
  update(projectId, keywordId, data) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const ok = SheetDB.updateRow(ssId, 'keywords', 'keyword_id', keywordId, data);
    if (!ok) throw new Error(`Keyword not found: ${keywordId}`);
    invalidateCache([cacheKey('keywords', projectId)]);
    return { keyword_id: keywordId, ...data };
  },

  /**
   * Xoá keyword
   */
  delete(projectId, keywordId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const ok = SheetDB.deleteRow(ssId, 'keywords', 'keyword_id', keywordId);
    if (!ok) throw new Error(`Keyword not found: ${keywordId}`);
    invalidateCache([cacheKey('keywords', projectId)]);
    return true;
  },

  /**
   * AI Classify Intent — gọi Gemini để phân loại từng keyword
   * KHÔNG ghi database — trả về preview để user confirm
   * @param {string[]} keywords — array keyword text
   * @returns {{ keyword: string, intent: string, confidence: string }[]}
   */
  classifyIntent(keywords) {
    if (!keywords || keywords.length === 0) throw new Error('keywords array is required');
    if (keywords.length > 50) throw new Error('Max 50 keywords per classify request');

    const schema = {
      type: 'ARRAY',
      items: {
        type: 'OBJECT',
        properties: {
          keyword:    { type: 'STRING' },
          intent:     { type: 'STRING', enum: ['informational', 'commercial', 'transactional', 'navigational'] },
          confidence: { type: 'STRING', enum: ['high', 'medium', 'low'] },
          reason:     { type: 'STRING' }
        },
        required: ['keyword', 'intent', 'confidence']
      }
    };

    const prompt = `Classify the search intent for each keyword. Return JSON array.
Keywords: ${JSON.stringify(keywords)}
Intent types: informational, commercial, transactional, navigational`;

    const result = callGeminiSafe(prompt, schema);
    if (!result) throw new Error('AI classification failed — try again');
    return result;
  },

  /**
   * Apply AI intent results vào database (sau khi user confirm)
   * @param {Object[]} updates — [{ keyword_id, intent }]
   */
  applyIntents(projectId, updates) {
    if (!updates || updates.length === 0) throw new Error('updates array is required');
    const ssId = ProjectService.getSpreadsheetId(projectId);
    let applied = 0;
    updates.forEach(({ keyword_id, intent }) => {
      if (SheetDB.updateRow(ssId, 'keywords', 'keyword_id', keyword_id, { intent })) applied++;
    });
    invalidateCache([cacheKey('keywords', projectId)]);
    Logger.log(`[KeywordService] INFO: Applied intents for ${applied}/${updates.length} keywords`);
    return { applied };
  }
};
