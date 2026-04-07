// services/KeywordService.gs — M2: Keyword Research & Mapping
// Quản lý keywords: CRUD, bulk import, map to silo, cannibalization detection, AI intent

const KEYWORD_SHEET = 'keywords';

// Status flow: researching → mapped → targeting → ranking → paused
const KEYWORD_STATUS = ['researching', 'mapped', 'targeting', 'ranking', 'paused'];
const KEYWORD_INTENT = ['informational', 'commercial', 'transactional', 'navigational'];
const KEYWORD_PRIORITY = ['critical', 'high', 'medium', 'low'];

const KeywordService = {

  // ── Helpers ─────────────────────────────────────────────────

  /**
   * Lấy spreadsheet ID của project
   * @param {string} projectId
   * @returns {string}
   */
  _ssId(projectId) {
    return ProjectService.getSpreadsheetId(projectId);
  },

  /**
   * Build 1 keyword row đầy đủ theo schema CORE_DATABASE.md
   * @param {Object} data
   * @returns {Object}
   */
  _buildRow(data) {
    return {
      keyword_id:       data.keyword_id      || uuid(),
      keyword:          String(data.keyword  || '').trim(),
      search_volume:    data.search_volume   != null ? Number(data.search_volume)  : '',
      difficulty:       data.difficulty      != null ? Number(data.difficulty)     : '',
      cpc:              data.cpc             != null ? Number(data.cpc)            : '',
      intent:           KEYWORD_INTENT.includes(data.intent) ? data.intent         : '',
      cluster_group:    data.cluster_group   || '',
      silo_id:          data.silo_id         || '',
      target_url:       data.target_url      || '',
      current_position: data.current_position != null ? Number(data.current_position) : '',
      priority:         KEYWORD_PRIORITY.includes(data.priority) ? data.priority    : 'medium',
      status:           KEYWORD_STATUS.includes(data.status)   ? data.status        : 'researching',
      lsi_keywords:     data.lsi_keywords    || '',
      source:           data.source          || 'manual',
      notes:            data.notes           || '',
    };
  },

  // ── CRUD ────────────────────────────────────────────────────

  /**
   * Lấy tất cả keywords của 1 project
   * @param {string} projectId
   * @returns {Object[]}
   */
  getAll(projectId) {
    const ssId = this._ssId(projectId);
    return getWithCache(
      cacheKey(KEYWORD_SHEET, projectId),
      () => SheetDB.getRows(ssId, KEYWORD_SHEET),
      CACHE_TTL.keywords
    );
  },

  /**
   * Thêm 1 keyword — kiểm tra trùng trước khi add
   * @param {string} projectId
   * @param {Object} data
   * @param {boolean} [overwrite=false] — true: update nếu trùng
   * @returns {{ row: Object, isDuplicate: boolean, updated: boolean }}
   */
  add(projectId, data, overwrite) {
    validateRequired(data, ['keyword']);
    overwrite = overwrite === true;
    const ssId = this._ssId(projectId);
    const kwText = String(data.keyword).trim().toLowerCase();

    // Kiểm tra trùng trong sheet hiện tại
    const existing = SheetDB.getRows(ssId, KEYWORD_SHEET);
    const duplicate = existing.find(r => r.keyword && r.keyword.toLowerCase().trim() === kwText);

    if (duplicate) {
      if (!overwrite) {
        // Trả về info duplicate để frontend quyết định
        return { row: duplicate, isDuplicate: true, updated: false };
      }
      // Overwrite: update row hiện có
      const row = this._buildRow({ ...duplicate, ...data, keyword_id: duplicate.keyword_id });
      SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', duplicate.keyword_id, row);
      invalidateCache([cacheKey(KEYWORD_SHEET, projectId), cacheKey('keyword_stats', projectId)]);
      Logger.log('[KeywordService] INFO: Overwritten keyword: ' + row.keyword);
      return { row, isDuplicate: true, updated: true };
    }

    // Không trùng: add mới
    const row = this._buildRow(data);
    SheetDB.addRow(ssId, KEYWORD_SHEET, row);
    invalidateCache([
      cacheKey(KEYWORD_SHEET, projectId),
      cacheKey('keyword_stats', projectId),
      cacheKey('cannibalization', projectId),
    ]);
    Logger.log('[KeywordService] INFO: Added keyword: ' + row.keyword);
    return { row, isDuplicate: false, updated: false };
  },

  /**
   * Bulk import từ CSV parse result
   * @param {string} projectId
   * @param {Object[]} rows
   * @param {boolean} [overwrite=false] — true: update trùng; false: skip trùng
   * @returns {{ imported, updated, skipped, duplicates: string[] }}
   */
  bulkImport(projectId, rows, overwrite) {
    if (!Array.isArray(rows) || rows.length === 0) throw new Error('rows must be a non-empty array');
    overwrite = overwrite === true;
    const ssId = this._ssId(projectId);

    // Build lookup existing keyword lowercase -> keyword_id
    const existing = SheetDB.getRows(ssId, KEYWORD_SHEET);
    var existingMap = {};
    existing.forEach(function(r) {
      if (r.keyword) existingMap[r.keyword.toLowerCase().trim()] = r.keyword_id;
    });

    var toAdd = [], toUpdate = [], skippedList = [];
    var self = KeywordService;
    rows.forEach(function(r) {
      var built = self._buildRow(r);
      if (!built.keyword) return;
      var key = built.keyword.toLowerCase().trim();
      if (existingMap[key]) {
        if (overwrite) toUpdate.push({ kwId: existingMap[key], data: built });
        else skippedList.push(built.keyword);
      } else {
        toAdd.push(built);
      }
    });

    if (toAdd.length > 0) SheetDB.batchAddRows(ssId, KEYWORD_SHEET, toAdd);
    toUpdate.forEach(function(item) {
      SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', item.kwId, item.data);
    });

    invalidateCache([
      cacheKey(KEYWORD_SHEET, projectId),
      cacheKey('keyword_stats', projectId),
      cacheKey('cannibalization', projectId),
    ]);
    Logger.log('[KeywordService] INFO: bulkImport added:' + toAdd.length + ' updated:' + toUpdate.length + ' skipped:' + skippedList.length);
    return { imported: toAdd.length, updated: toUpdate.length, skipped: skippedList.length, duplicates: skippedList };
  },

  /**
   * Cập nhật keyword (1 hoặc nhiều field)
   * @param {string} projectId
   * @param {string} keywordId
   * @param {Object} data
   * @returns {Object}
   */
  update(projectId, keywordId, data) {
    const ssId = this._ssId(projectId);
    if (data.intent    && !KEYWORD_INTENT.includes(data.intent))     throw new Error('Invalid intent: ' + data.intent);
    if (data.priority  && !KEYWORD_PRIORITY.includes(data.priority)) throw new Error('Invalid priority: ' + data.priority);
    if (data.status    && !KEYWORD_STATUS.includes(data.status))     throw new Error('Invalid status: ' + data.status);

    const ok = SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', keywordId, data);
    if (!ok) throw new Error('Keyword not found: ' + keywordId);
    invalidateCache([
      cacheKey(KEYWORD_SHEET, projectId),
      cacheKey('keyword_stats', projectId),
      cacheKey('cannibalization', projectId),
    ]);
    return Object.assign({ keyword_id: keywordId }, data);
  },

  /**
   * Xoá keyword (chỉ xóa khi chưa map vào silo)
   * @param {string} projectId
   * @param {string} keywordId
   * @returns {boolean}
   */
  delete(projectId, keywordId) {
    const ssId = this._ssId(projectId);
    const rows = SheetDB.getRows(ssId, KEYWORD_SHEET);
    const kw = rows.find(r => r.keyword_id === keywordId);
    if (!kw) throw new Error('Keyword not found: ' + keywordId);
    if (kw.silo_id && kw.status !== 'researching') {
      throw new Error("Cannot delete mapped keyword — set status to 'paused' instead");
    }
    const ok = SheetDB.deleteRow(ssId, KEYWORD_SHEET, 'keyword_id', keywordId);
    if (!ok) throw new Error('Delete failed for keyword: ' + keywordId);
    invalidateCache([
      cacheKey(KEYWORD_SHEET, projectId),
      cacheKey('keyword_stats', projectId),
      cacheKey('cannibalization', projectId),
    ]);
    return true;
  },

  // ── Mapping ─────────────────────────────────────────────────

  /**
   * Map keyword vào silo
   * @param {string} projectId
   * @param {string} keywordId
   * @param {string} siloId
   * @param {string} targetUrl
   * @returns {Object}
   */
  mapToSilo(projectId, keywordId, siloId, targetUrl) {
    if (!siloId) throw new Error('siloId is required');
    const ssId = this._ssId(projectId);
    const silos = SheetDB.getRows(ssId, 'silo_structure');
    const silo = silos.find(s => s.silo_id === siloId);
    if (!silo) throw new Error('Silo not found: ' + siloId);
    const updateData = { silo_id: siloId, target_url: targetUrl || silo.target_url || '', status: 'mapped' };
    const ok = SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', keywordId, updateData);
    if (!ok) throw new Error('Keyword not found: ' + keywordId);
    invalidateCache([
      cacheKey(KEYWORD_SHEET, projectId),
      cacheKey('keyword_stats', projectId),
      cacheKey('cannibalization', projectId),
    ]);
    Logger.log('[KeywordService] INFO: Mapped keyword ' + keywordId + ' to silo ' + siloId);
    return Object.assign({ keyword_id: keywordId }, updateData);
  },

  /**
   * Unmap keyword khỏi silo
   */
  unmapFromSilo(projectId, keywordId) {
    const updateData = { silo_id: '', target_url: '', status: 'researching' };
    const ssId = this._ssId(projectId);
    const ok = SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', keywordId, updateData);
    if (!ok) throw new Error('Keyword not found: ' + keywordId);
    invalidateCache([
      cacheKey(KEYWORD_SHEET, projectId),
      cacheKey('keyword_stats', projectId),
      cacheKey('cannibalization', projectId),
    ]);
    return Object.assign({ keyword_id: keywordId }, updateData);
  },

  // ── Cannibalization ─────────────────────────────────────────

  /**
   * Phát hiện cannibalization: 2+ keywords có cùng target_url VÀ cùng intent
   */
  detectCannibalization(projectId) {
    const ckCannibal = cacheKey('cannibalization', projectId);
    return getWithCache(ckCannibal, () => {
      const ssId = this._ssId(projectId);
      const rows = SheetDB.getRows(ssId, KEYWORD_SHEET);
      const mapped = rows.filter(r => r.target_url && r.intent);
      const groups = {};
      mapped.forEach(kw => {
        const key = kw.target_url + '||' + kw.intent;
        if (!groups[key]) groups[key] = { url: kw.target_url, intent: kw.intent, keywords: [] };
        groups[key].keywords.push({ keyword_id: kw.keyword_id, keyword: kw.keyword, search_volume: kw.search_volume, priority: kw.priority, status: kw.status });
      });
      const result = Object.values(groups).filter(g => g.keywords.length >= 2);
      Logger.log('[KeywordService] INFO: Cannibalization check: ' + result.length + ' conflicts for project ' + projectId);
      return result;
    }, CACHE_TTL.cannibalization);
  },

  // ── AI Intent ───────────────────────────────────────────────

  /**
   * AI Classify Intent — preview-first pattern (không ghi DB)
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
          intent:     { type: 'STRING', enum: KEYWORD_INTENT },
          confidence: { type: 'STRING', enum: ['high', 'medium', 'low'] },
          reason:     { type: 'STRING' }
        },
        required: ['keyword', 'intent', 'confidence']
      }
    };
    const prompt = 'You are an SEO expert. Classify the search intent for each Vietnamese/English keyword below.\nReturn a JSON array with exact same order as input.\n\nKeywords: ' + JSON.stringify(keywords) + '\n\nIntent definitions:\n- informational: user wants to learn something\n- commercial: user is comparing options before buying\n- transactional: user wants to buy/hire/sign up now\n- navigational: user wants to find a specific website/page';
    const result = callGeminiSafe(prompt, schema);
    if (!result) throw new Error('AI classification failed — try again');
    return result;
  },

  /**
   * Apply AI intent results vào database
   */
  applyIntents(projectId, updates) {
    if (!updates || updates.length === 0) throw new Error('updates array is required');
    const ssId = this._ssId(projectId);
    let applied = 0, failed = 0;
    updates.forEach(({ keyword_id, intent }) => {
      if (!KEYWORD_INTENT.includes(intent)) { failed++; return; }
      SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', keyword_id, { intent }) ? applied++ : failed++;
    });
    invalidateCache([cacheKey(KEYWORD_SHEET, projectId), cacheKey('keyword_stats', projectId), cacheKey('cannibalization', projectId)]);
    Logger.log('[KeywordService] INFO: Applied intents ' + applied + '/' + updates.length + ' (failed: ' + failed + ')');
    return { applied, failed };
  },

  // ── Stats ────────────────────────────────────────────────────

  getStats(projectId) {
    const ckStats = cacheKey('keyword_stats', projectId);
    return getWithCache(ckStats, () => {
      const rows = this.getAll(projectId);
      const total  = rows.length;
      const mapped = rows.filter(r => r.silo_id).length;
      const kds = rows.map(r => Number(r.difficulty)).filter(d => !isNaN(d) && d > 0);
      const avgKd = kds.length ? Math.round(kds.reduce((a, b) => a + b, 0) / kds.length) : 0;
      const intentBreakdown = KEYWORD_INTENT.reduce((acc, intent) => {
        acc[intent] = rows.filter(r => r.intent === intent).length;
        return acc;
      }, {});
      return { total, mapped, mappedPct: total ? Math.round((mapped / total) * 100) : 0, avgKd, intentBreakdown };
    }, CACHE_TTL.keyword_stats);
  },

  // ── AI Cluster Suggest ──────────────────────────────────────

  suggestClusters(projectId, niche) {
    niche = niche || '';
    const rows = this.getAll(projectId);
    if (rows.length === 0) throw new Error('No keywords to cluster');
    const kws = rows.slice(0, 100).map(r => r.keyword);
    const schema = {
      type: 'ARRAY',
      items: {
        type: 'OBJECT',
        properties: {
          cluster_name: { type: 'STRING' },
          keywords:     { type: 'ARRAY', items: { type: 'STRING' } },
          rationale:    { type: 'STRING' }
        },
        required: ['cluster_name', 'keywords']
      }
    };
    const prompt = 'You are an SEO expert specializing in keyword clustering.\nGroup the following ' + kws.length + ' keywords into logical topic clusters.\n' + (niche ? 'Website niche: ' + niche + '\n' : '') + 'Rules:\n- Create 3-10 clusters\n- Each keyword must appear in exactly one cluster\n- cluster_name should be short (2-5 words), descriptive in the same language as keywords\n- rationale: brief explanation\n\nKeywords: ' + JSON.stringify(kws);
    const result = callGeminiSafe(prompt, schema);
    if (!result) throw new Error('AI clustering failed — try again');
    Logger.log('[KeywordService] INFO: AI suggested ' + result.length + ' clusters');
    return result;
  },

  applyClusters(projectId, clusters) {
    const ssId = this._ssId(projectId);
    const rows = SheetDB.getRows(ssId, KEYWORD_SHEET);
    const lookup = {};
    rows.forEach(r => { if (r.keyword) lookup[r.keyword.toLowerCase()] = r.keyword_id; });
    let applied = 0, failed = 0;
    clusters.forEach(cluster => {
      cluster.keywords.forEach(kwText => {
        const kwId = lookup[kwText.toLowerCase()];
        if (!kwId) { failed++; return; }
        SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', kwId, { cluster_group: cluster.cluster_name }) ? applied++ : failed++;
      });
    });
    invalidateCache([cacheKey(KEYWORD_SHEET, projectId)]);
    return { applied, failed };
  },

  bulkUpdate(projectId, keywordIds, data) {
    if (!keywordIds || keywordIds.length === 0) throw new Error('keywordIds is required');
    const allowed = ['status', 'cluster_group', 'intent', 'priority'];
    const safeData = Object.fromEntries(Object.entries(data).filter(([k]) => allowed.includes(k)));
    if (Object.keys(safeData).length === 0) throw new Error('No valid fields to update');
    const ssId = this._ssId(projectId);
    let updated = 0;
    keywordIds.forEach(kwId => { if (SheetDB.updateRow(ssId, KEYWORD_SHEET, 'keyword_id', kwId, safeData)) updated++; });
    invalidateCache([cacheKey(KEYWORD_SHEET, projectId), cacheKey('keyword_stats', projectId), cacheKey('cannibalization', projectId)]);
    return { updated };
  },
};
