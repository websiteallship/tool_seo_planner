// Triggers.gs — Scheduled jobs (tất cả triggers tập trung ở đây)
// Tối đa 20 triggers — quản lý tập trung, không tạo rải rác

/**
 * Khởi tạo tất cả triggers — xoá cũ, tạo mới
 * Chạy 1 lần khi setup hoặc deploy lại
 */
function initTriggers() {
  // Xoá tất cả triggers cũ
  ScriptApp.getProjectTriggers().forEach(t => ScriptApp.deleteTrigger(t));

  // Daily 2:00 AM — Sync GSC performance (3 ngày gần nhất)
  ScriptApp.newTrigger('dailySync')
    .timeBased()
    .atHour(2)
    .everyDays(1)
    .create();

  // Sunday 3:00 AM — Batch URL Inspection (top 100 pillar pages)
  ScriptApp.newTrigger('weeklyInspection')
    .timeBased()
    .onWeekDay(ScriptApp.WeekDay.SUNDAY)
    .atHour(3)
    .create();

  // Monday 4:00 AM — Kiểm tra sitemap status
  ScriptApp.newTrigger('weeklySitemapCheck')
    .timeBased()
    .onWeekDay(ScriptApp.WeekDay.MONDAY)
    .atHour(4)
    .create();

  // Daily 3:00 AM — Tính toán position changes, movers
  ScriptApp.newTrigger('dailyRankingUpdate')
    .timeBased()
    .atHour(3)
    .everyDays(1)
    .create();

  Logger.log('[Triggers] INFO: All triggers initialized (4 total)');
}

// ─── Trigger Handler Functions ───────────────────────────────

function dailySync() {
  Logger.log('[Triggers] INFO: dailySync started');
  try {
    const projects = ProjectService.getAll().filter(p => p.status === 'active' && p.gsc_site_url);
    projects.forEach(p => {
      const lock = LockService.getScriptLock();
      if (!lock.tryLock(5000)) {
        Logger.log(`[Triggers] WARN: Skip project ${p.project_id} — lock busy`);
        return;
      }
      try {
        GSCService.syncPerformance(p.project_id);
      } finally {
        lock.releaseLock();
      }
    });
  } catch (e) {
    Logger.log('[Triggers] ERROR dailySync: ' + e.message);
  }
}

function weeklyInspection() {
  Logger.log('[Triggers] INFO: weeklyInspection started');
  try {
    const projects = ProjectService.getAll().filter(p => p.status === 'active' && p.gsc_site_url);
    projects.forEach(p => GSCService.inspectUrls(p.project_id));
  } catch (e) {
    Logger.log('[Triggers] ERROR weeklyInspection: ' + e.message);
  }
}

function weeklySitemapCheck() {
  Logger.log('[Triggers] INFO: weeklySitemapCheck started');
  // TODO: implement khi GSCService.checkSitemap() sẵn sàng
}

function dailyRankingUpdate() {
  Logger.log('[Triggers] INFO: dailyRankingUpdate started');
  // TODO: implement khi RankingService sẵn sàng
}
