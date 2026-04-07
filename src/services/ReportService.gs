// services/ReportService.gs — M12: Client Reporting
// Auto-generate monthly SEO report (Google Slides / Docs) với AI narrative

const ReportService = {
  /**
   * Generate Monthly SEO Report — tạo Google Slides từ template
   * @param {string} projectId
   * @param {Object} options — { month, year, template_id? }
   */
  generate(projectId, options = {}) {
    const project = SheetDB.findOne(
      ProjectService._getMasterSheetId(), '_projects', 'project_id', projectId
    );
    if (!project) throw new Error(`Project not found: ${projectId}`);

    const stats = DashboardService.getStats(projectId);
    const gsc = stats.gsc || {};
    const month = options.month || Utilities.formatDate(new Date(), 'Asia/Ho_Chi_Minh', 'MMMM yyyy');

    // AI tóm tắt insights
    const summary = ReportService._generateAISummary(project, stats, month);

    // Data để merge vào Slides template
    const data = {
      client_name: project.client_name || project.name,
      domain: project.domain,
      month,
      total_clicks: gsc.clicks || 0,
      total_impressions: gsc.impressions || 0,
      avg_ctr: gsc.ctr || '0',
      avg_position: gsc.avg_position || '—',
      total_keywords: stats.keywords.total,
      mapped_keywords: stats.keywords.mapped,
      total_silo_pages: stats.silo.total,
      indexed_pages: stats.silo.indexed,
      audit_completion: stats.audit ? stats.audit.completion : 0,
      ai_summary: summary || 'N/A'
    };

    // Nếu có template_id thì dùng, không thì tạo Google Doc basic
    if (options.template_id) {
      const slideUrl = createSlidesFromTemplate(options.template_id, data);
      return { type: 'slides', url: slideUrl };
    } else {
      const docUrl = ReportService._createBasicDoc(data);
      return { type: 'doc', url: docUrl };
    }
  },

  /**
   * Tạo Google Doc report cơ bản khi không có Slides template
   */
  _createBasicDoc(data) {
    const doc = DocumentApp.create(`SEO Report — ${data.client_name} — ${data.month}`);
    const body = doc.getBody();

    body.appendParagraph(`SEO Monthly Report`).setHeading(DocumentApp.ParagraphHeading.HEADING1);
    body.appendParagraph(`${data.client_name} · ${data.domain} · ${data.month}`);
    body.appendParagraph('');
    body.appendParagraph('Key Performance Indicators').setHeading(DocumentApp.ParagraphHeading.HEADING2);
    body.appendParagraph(`Clicks: ${data.total_clicks} | Impressions: ${data.total_impressions} | CTR: ${data.avg_ctr}% | Avg Position: ${data.avg_position}`);
    body.appendParagraph('');
    body.appendParagraph('Content & SEO Progress').setHeading(DocumentApp.ParagraphHeading.HEADING2);
    body.appendParagraph(`Keywords: ${data.total_keywords} (${data.mapped_keywords} mapped)`);
    body.appendParagraph(`Silo Pages: ${data.total_silo_pages} (${data.indexed_pages} indexed)`);
    body.appendParagraph(`Audit Completion: ${data.audit_completion}%`);
    body.appendParagraph('');
    body.appendParagraph('AI Insights').setHeading(DocumentApp.ParagraphHeading.HEADING2);
    body.appendParagraph(data.ai_summary);

    doc.saveAndClose();
    return `https://docs.google.com/document/d/${doc.getId()}`;
  },

  /**
   * AI tóm tắt insights từ stats data
   */
  _generateAISummary(project, stats, month) {
    const prompt = `You are an SEO analyst. Write a 3-sentence executive summary for this SEO monthly report:
Client: ${project.client_name || project.name}
Month: ${month}
GSC: ${JSON.stringify(stats.gsc)}
Keywords: ${JSON.stringify(stats.keywords)}
Silo: ${JSON.stringify(stats.silo)}
Be professional and highlight key wins and areas for improvement.`;

    const result = callGeminiSafe(prompt);
    return result ? (typeof result === 'string' ? result : JSON.stringify(result)) : null;
  }
};
