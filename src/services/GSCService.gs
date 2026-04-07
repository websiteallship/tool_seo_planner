// services/GSCService.gs — M10: Google Search Console Integration
// Sync performance data, URL Inspection, Sitemap check

const GSCService = {
  /**
   * Lấy danh sách GSC properties của user (cached)
   */
  listProperties() {
    return getWithCache(
      'gsc:properties:list',
      () => listGSCProperties(),
      CACHE_TTL.gsc_properties
    );
  },

  /**
   * Sync performance data 3 ngày gần nhất vào sheet gsc_performance
   * Dùng LockService để tránh concurrent sync
   */
  syncPerformance(projectId) {
    const lock = LockService.getScriptLock();
    if (!lock.tryLock(5000)) return { error: 'Another sync is running for this project' };

    try {
      const project = SheetDB.findOne(
        ProjectService._getMasterSheetId(),
        '_projects', 'project_id', projectId
      );
      if (!project || !project.gsc_site_url) throw new Error('Project has no GSC site URL configured');

      const ssId = project.spreadsheet_id;
      const endDate = formatDate(new Date());
      const startDate = formatDate(new Date(Date.now() - 3 * 24 * 60 * 60 * 1000));

      const rows = querySearchAnalytics(project.gsc_site_url, {
        startDate,
        endDate,
        dimensions: ['query', 'page', 'date', 'device', 'country']
      });

      const now = nowISO();
      const dataRows = rows.map(r => ({
        record_id: uuid(),
        date: r.keys[2] || '',
        query: r.keys[0] || '',
        page: r.keys[1] || '',
        clicks: r.clicks || 0,
        impressions: r.impressions || 0,
        ctr: r.ctr ? (r.ctr * 100).toFixed(2) : 0,
        position: r.position ? r.position.toFixed(1) : '',
        device: r.keys[3] || '',
        country: r.keys[4] || '',
        synced_at: now
      }));

      if (dataRows.length > 0) {
        SheetDB.batchAddRows(ssId, 'gsc_performance', dataRows);
      }

      // Enrich keyword module với current_position
      GSCService._enrichKeywordPositions(projectId, ssId, rows);

      Logger.log(`[GSCService] INFO: Synced ${dataRows.length} rows for project ${projectId}`);
      return { synced: dataRows.length, startDate, endDate };
    } finally {
      lock.releaseLock();
    }
  },

  /**
   * Update current_position trong keyword sheet từ GSC data
   */
  _enrichKeywordPositions(projectId, ssId, gscRows) {
    if (!gscRows || gscRows.length === 0) return;
    const queryPositions = {};
    gscRows.forEach(r => {
      const q = r.keys[0];
      if (!queryPositions[q] || r.position < queryPositions[q]) {
        queryPositions[q] = r.position;
      }
    });

    const keywords = SheetDB.getRows(ssId, 'keywords');
    keywords.forEach(kw => {
      const pos = queryPositions[kw.keyword.toLowerCase()];
      if (pos !== undefined) {
        SheetDB.updateRow(ssId, 'keywords', 'keyword_id', kw.keyword_id, {
          current_position: pos.toFixed(1)
        });
      }
    });
    invalidateCache([cacheKey('keywords', projectId)]);
  },

  /**
   * Batch URL Inspection cho top pillar pages (silo level 1)
   * Giới hạn 10 URLs/run tránh timeout
   */
  inspectUrls(projectId) {
    const project = SheetDB.findOne(
      ProjectService._getMasterSheetId(), '_projects', 'project_id', projectId
    );
    if (!project || !project.gsc_site_url) throw new Error('No GSC site URL');

    const ssId = project.spreadsheet_id;
    const silos = SheetDB.getRows(ssId, 'silo_structure').filter(s => s.level == 1).slice(0, 10);

    const results = silos.map(silo => {
      try {
        const url = `${project.gsc_site_url.replace(/\/$/, '')}/${silo.slug}`;
        const result = inspectUrl(project.gsc_site_url, url);
        const indexStatus = result?.indexStatusResult?.verdict || 'UNKNOWN';
        SheetDB.updateRow(ssId, 'silo_structure', 'silo_id', silo.silo_id, { index_status: indexStatus });
        return { silo_id: silo.silo_id, url, index_status: indexStatus };
      } catch (e) {
        Logger.log(`[GSCService] WARN: inspectUrl failed for ${silo.slug}: ${e.message}`);
        return { silo_id: silo.silo_id, error: e.message };
      }
    });

    invalidateCache([cacheKey('silo_tree', projectId)]);
    return results;
  },

  /**
   * Tổng hợp stats từ gsc_performance sheet
   */
  getStats(projectId) {
    const ssId = ProjectService.getSpreadsheetId(projectId);
    const rows = SheetDB.getRows(ssId, 'gsc_performance');
    if (rows.length === 0) return { clicks: 0, impressions: 0, ctr: 0, avg_position: 0 };

    const totalClicks = rows.reduce((s, r) => s + Number(r.clicks || 0), 0);
    const totalImpressions = rows.reduce((s, r) => s + Number(r.impressions || 0), 0);
    const avgPosition = rows.reduce((s, r) => s + Number(r.position || 0), 0) / rows.length;

    return {
      clicks: totalClicks,
      impressions: totalImpressions,
      ctr: totalImpressions > 0 ? (totalClicks / totalImpressions * 100).toFixed(2) : 0,
      avg_position: avgPosition.toFixed(1),
      total_rows: rows.length
    };
  }
};
