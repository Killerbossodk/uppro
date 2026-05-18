<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\CompteRenduController;
use App\Http\Controllers\Api\JuryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\SpecialiteController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ArchivedProjectController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PhaseController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ReportCommentController;
use App\Http\Controllers\Api\ReportAnnotationController;
use App\Http\Controllers\Api\ReunionController;
use App\Http\Controllers\Api\EncadreurFeedbackController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ============================================
    // UTILISATEURS
    // ============================================
    Route::get('/users', [UserController::class, 'index']);

    // ============================================
    // PROJETS (ordre correct : routes spécifiques AVANT route paramétrée)
    // ============================================
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/archived', [ProjectController::class, 'archived']);
    Route::get('/projects/archives', [ProjectController::class, 'archives']);
    Route::get('/projects/my-archived', [ProjectController::class, 'myArchived']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    // ============================================
    // GROUPES
    // ============================================
    Route::get('/groups', [GroupController::class, 'index']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::get('/groups/{group}/reports', [ReportController::class, 'groupReports']);
    Route::post('/groups/{group}/set-chef', [GroupController::class, 'setChef']);

    // ============================================
    // RAPPORTS RUP
    // ============================================
    Route::middleware(['role:rup_projet,rup_specialite'])->group(function () {
        Route::get('/rup-reports', [ReportController::class, 'rupReports']);
    });

    // ============================================
    // MEETINGS (Rendez-vous)
    // ============================================
    Route::get('/meetings', [MeetingController::class, 'index']);
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('/meetings/{meeting}/start-soutenance', [MeetingController::class, 'startSoutenance']);
    Route::post('/meetings/{meeting}/finish-soutenance', [MeetingController::class, 'finishSoutenance']);
    Route::post('/meetings/{meeting}/save-critiques', [MeetingController::class, 'saveCritiques']);
    Route::get('/meetings/{meeting}/jury', [JuryController::class, 'showByMeeting']);
    Route::post('/meetings/{meeting}/grade', [GradeController::class, 'storeOrUpdate']);
    Route::get('/meetings/{meeting}/suggest-grade', [GradeController::class, 'suggestGrade']);

    // ============================================
    // RAPPORTS
    // ============================================
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::get('/reports/{report}/text', [ReportController::class, 'getTextContent']);
    Route::get('/reports/{report}/export-pdf', [ReportController::class, 'exportReportPdf']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy']);

    // ============================================
    // COMPTES RENDUS
    // ============================================
    Route::get('/meetings/{meeting}/compte-rendu', [CompteRenduController::class, 'show']);
    Route::post('/meetings/{meeting}/generate-draft', [CompteRenduController::class, 'generateDraft']);

    // ============================================
    // JURYS
    // ============================================
    Route::get('/juries', [JuryController::class, 'index']);
    Route::get('/juries/{jury}', [JuryController::class, 'show']);
    Route::post('/juries', [JuryController::class, 'store']);
    Route::put('/juries/{jury}', [JuryController::class, 'update']);
    Route::delete('/juries/{jury}', [JuryController::class, 'destroy']);
    Route::post('/juries/{jury}/notify', [JuryController::class, 'notify']);

    // ============================================
    // NOTES
    // ============================================
    Route::get('/grades/{grade}', [GradeController::class, 'show']);
    Route::prefix('meetings/{meeting}')->group(function () {
        Route::get('/grade', [GradeController::class, 'showByMeeting']);
    });

    // ============================================
    // CHAT
    // ============================================
    Route::prefix('chat')->group(function () {
        Route::get('/contacts', [ChatController::class, 'getContacts']);
        Route::get('/private/{receiver}', [ChatController::class, 'indexPrivate']);
        Route::post('/private/{receiver}', [ChatController::class, 'storePrivate']);
        Route::post('/private/{sender}/read', [ChatController::class, 'markPrivateAsRead']);
        Route::get('/{group}', [ChatController::class, 'index']);
        Route::post('/{group}', [ChatController::class, 'store']);
        Route::post('/{group}/read', [ChatController::class, 'markAsRead']);
    });

    // ============================================
    // SPÉCIALITÉS
    // ============================================
    Route::get('/specialites', [SpecialiteController::class, 'index']);

    // ============================================
    // PROJETS ARCHIVÉS (ancienne route - compatibilité)
    // ============================================
    Route::get('/archived-projects', [ArchivedProjectController::class, 'index']);
    Route::get('/archived-projects/{project}', [ArchivedProjectController::class, 'show']);

    // ============================================
    // NOTIFICATIONS
    // ============================================
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    // ============================================
    // PHASES
    // ============================================
    Route::get('/groups/{group}/phases', [PhaseController::class, 'index']);
    Route::post('/groups/{group}/phases', [PhaseController::class, 'store']);
    Route::put('/phases/{phase}', [PhaseController::class, 'update']);
    Route::delete('/phases/{phase}', [PhaseController::class, 'destroy']);

    // ============================================
    // ANNONCES
    // ============================================
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/latest', [AnnouncementController::class, 'latest']);
    Route::get('/announcements/carousel', [AnnouncementController::class, 'carousel']);

    // ============================================
    // EXPORTS
    // ============================================
    Route::get('/export/projects', [ExportController::class, 'exportProjects']);
    Route::get('/export/groups', [ExportController::class, 'exportGroups']);
    Route::get('/export/grades', [ExportController::class, 'exportGrades']);
    Route::get('/export/projects/pdf', [ExportController::class, 'exportProjectsPDF']);

    // ============================================
    // ANALYTICS
    // ============================================
    Route::middleware(['role:rup_projet,rup_specialite'])->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index']);
        Route::get('/export/analytics/pdf', [ExportController::class, 'exportAnalyticsPDF']);
    });

    // ============================================
    // INTELLIGENCE ARTIFICIELLE
    // ============================================
    Route::prefix('ai')->group(function () {
        Route::post('/chat', [AiController::class, 'chat']);
        Route::post('/analyze-section', [AiController::class, 'analyzeSection']);
        Route::post('/correct-grammar', [AiController::class, 'correctGrammar']);
        Route::post('/summarize', [AiController::class, 'summarize']);
        Route::post('/rephrase', [AiController::class, 'rephrase']);
        Route::post('/suggest-improvements', [AiController::class, 'suggestImprovements']);
        Route::post('/expand-content', [AiController::class, 'expandContent']);
        Route::post('/recommend-projects/chat', [AiController::class, 'recommendProjectsChat']);
        Route::post('/analyze-report/{report}', [AiController::class, 'analyzeReport']);
        Route::post('/suggest-grade/{report}', [AiController::class, 'suggestGrade']);
        Route::get('/recommend-projects', [AiController::class, 'recommendProjects']);
        Route::post('/chat-with-stats', [AiController::class, 'chatWithStats']);
        Route::post('/suggest-resources', [AiController::class, 'suggestResources']);
        Route::post('/generate-phases', [AiController::class, 'generatePhases']);
    });

    // ============================================
    // COMMENTAIRES SUR RAPPORTS
    // ============================================
    Route::get('/reports/{report}/comments', [ReportCommentController::class, 'index']);
    Route::post('/reports/{report}/comments', [ReportCommentController::class, 'store']);

    // ============================================
    // ANNOTATIONS SUR RAPPORTS
    // ============================================
    Route::get('/reports/{report}/annotations', [ReportAnnotationController::class, 'index']);
    Route::post('/reports/{report}/annotations', [ReportAnnotationController::class, 'store']);
    Route::delete('/annotations/{annotation}', [ReportAnnotationController::class, 'destroy']);

    // ============================================
    // ROUTES PAR RÔLE
    // ============================================

    // ---------- PROFESSEUR ----------
   // Professeur
    Route::middleware(['role:professeur'])->group(function () {
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::post('/projects/{project}/soumettre', [ProjectController::class, 'soumettre']);

        Route::post('/groups', [GroupController::class, 'store']);
        Route::post('/groups/{group}/add-member', [GroupController::class, 'addMember']);
        Route::delete('/groups/{group}/remove-member', [GroupController::class, 'removeMember']);
        Route::post('/groups/{group}/toggle-inscriptions', [GroupController::class, 'toggleInscriptions']);
        Route::get('/projects/my-archived', [ProjectController::class, 'myArchived']);

        Route::post('/meetings', [MeetingController::class, 'store']);
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update']);
        Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy']);
        Route::post('/meetings/{meeting}/compte-rendu', [CompteRenduController::class, 'store']);
        Route::post('/groups/{group}/report-to-rup', [ReportController::class, 'reportToRup']);
        Route::post('/groups/{group}/save-rup-report', [ReportController::class, 'saveRupReport']);
        
    });

    // ---------- RAPPORTS (VALIDATION PARTAGÉE) ----------
    Route::post('/reports/{report}/validate', [ReportController::class, 'validateReport']);
    Route::post('/reports/{report}/reject', [ReportController::class, 'rejectReport']);

    // ---------- ÉTUDIANT ----------
    Route::middleware(['role:etudiant'])->group(function () {
        Route::post('/groups/{group}/self-add', [GroupController::class, 'selfAdd']);
        Route::post('/groups/{group}/leave', [GroupController::class, 'leaveGroup']);
        Route::post('/meetings/{meeting}/respond', [MeetingController::class, 'respond']);
        Route::post('/meetings/request', [MeetingController::class, 'requestMeeting']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::post('/groups/{group}/delegate', [ReportController::class, 'delegate']);
    });

    // ---------- GLOBAL SETTINGS & FEEDBACKS ----------
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::post('/feedbacks/encadreurs', [EncadreurFeedbackController::class, 'store']);
    Route::get('/feedbacks/encadreurs', [EncadreurFeedbackController::class, 'index']);

    // ---------- RUP PROJET ----------
    Route::middleware(['role:rup_projet'])->group(function () {
        Route::post('/projects/{project}/valider', [ProjectController::class, 'valider']);
        Route::post('/projects/{project}/refuser', [ProjectController::class, 'refuser']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/import', [UserController::class, 'import']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::post('/settings/start-year', [SettingsController::class, 'startYear']);
        Route::post('/settings/close-year', [SettingsController::class, 'closeYear']);
        Route::get('/feedbacks/encadreurs/stats', [EncadreurFeedbackController::class, 'stats']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
        Route::post('/groups/{group}/dissoudre', [GroupController::class, 'dissoudre']);
        
        // Gestion des archives
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
        Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
        Route::delete('/projects/{project}/force-delete', [ProjectController::class, 'forceDelete']);

        // Soutenances & Jurys
        Route::post('/meetings', [MeetingController::class, 'store']);

    });

    // ---------- RUP SPÉCIALITÉ ----------
    Route::middleware(['role:rup_specialite'])->group(function () {

        
        Route::prefix('meetings/{meeting}')->group(function () {
            Route::post('/grade/validate', [GradeController::class, 'validateGradeByMeeting']);
            Route::post('/grade/publish', [GradeController::class, 'publishByMeeting']);
        });
        
        Route::post('/grades/{grade}/validate', [GradeController::class, 'validateGrade']);
        Route::post('/grades/{grade}/publish', [GradeController::class, 'publish']);
    });

    // ============================================
    // RÉUNIONS (RUP Spécialité + professeurs)
    // ============================================
    Route::prefix('reunions')->group(function () {
        Route::get('/', [ReunionController::class, 'index']);
        Route::get('/{reunion}', [ReunionController::class, 'show']);
        
        Route::middleware(['role:rup_specialite'])->group(function () {
            Route::post('/', [ReunionController::class, 'store']);
            Route::delete('/{reunion}', [ReunionController::class, 'destroy']);
        });
        
        Route::post('/{reunion}/respond', [ReunionController::class, 'respond']);
    });
});

