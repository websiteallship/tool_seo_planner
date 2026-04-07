// services/DashboardService.gs — M11: Basic Dashboard
// Aggregate KPI stats từ tất cả modules

const DashboardService = {
  /**
   * Tổng hợp KPI cards cho dashboard
   * @param {string} projectId
   */
  getStats(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);

    // Keywords stats
    const keywords = SheetDB.getRows(ssId, 'keywords');
    const mappedKeywords = keywords.filter(k => k.silo_id && k.silo_id !== '');
    const intentDist = { informational: 0, commercial: 0, transactional: 0, navigational: 0, none: 0 };
    keywords.forEach(k => {
      const intent = k.intent || 'none';
      if (intent in intentDist) intentDist[intent]++;
      else intentDist.none++;
    });

    // Silo stats
    const silos = SheetDB.getRows(ssId, 'silo_structure');
    const indexedSilos = silos.filter(s => s.index_status === 'PASS').length;

    // Outline stats
    const outlines = SheetDB.getRows(ssId, 'content_outlines');
    const outlineByStatus = {};
    outlines.forEach(o => {
      outlineByStatus[o.status] = (outlineByStatus[o.status] || 0) + 1;
    });

    // Audit stats
    let auditStats = null;
    try { auditStats = AuditService.getStats(projectId); } catch (e) { /* không fail nếu chưa có checklist */ }

    // GSC stats
    let gscStats = null;
    try { gscStats = GSCService.getStats(projectId); } catch (e) { /* chưa sync */ }

    return {
      keywords: {
        total: keywords.length,
        mapped: mappedKeywords.length,
        mapped_pct: keywords.length > 0 ? Math.round(mappedKeywords.length / keywords.length * 100) : 0,
        intent_distribution: intentDist
      },
      silo: {
        total: silos.length,
        indexed: indexedSilos,
        indexed_pct: silos.length > 0 ? Math.round(indexedSilos / silos.length * 100) : 0
      },
      outlines: {
        total: outlines.length,
        by_status: outlineByStatus
      },
      audit: auditStats,
      gsc: gscStats
    };
  }
};
