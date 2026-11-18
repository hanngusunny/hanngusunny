<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceCheckController;
use App\Http\Controllers\TeacherCommentsController;
use App\Http\Controllers\StudentReflectionsController;
use App\Http\Controllers\ActivityPulseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ConsultController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\PublicCommentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\HotlineController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\MediaFileController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AdminLanguageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CmsEmbedController;
use App\Http\Controllers\Admin\ConsultLeadController;
use App\Http\Controllers\Admin\ContentCategoryController;
use App\Http\Controllers\Admin\ContentPostController;
use App\Http\Controllers\Admin\ContentTagController;
use App\Http\Controllers\Admin\ContentTemplateController;
use App\Http\Controllers\Admin\CustomFormController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FrontendAppearanceController;
use App\Http\Controllers\Admin\NavMenuController;
use App\Http\Controllers\Admin\PostCommentController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SchemaMigrateController;
use App\Http\Controllers\Admin\SiteBlockController;
use App\Http\Controllers\Admin\SiteCustomizeController;
use App\Http\Controllers\Admin\SiteMediaController;
use App\Http\Controllers\Admin\SitePageController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\AdminUiSettingsController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DatabaseMaintenanceController;
use App\Http\Controllers\Admin\CourseMemberController;
use App\Http\Controllers\Admin\CourseScheduleController;
use App\Http\Controllers\Admin\StudyProgramAssessmentController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Admin\StudyProgramKnowledgeReviewController;
use App\Http\Controllers\Admin\StudyProgramSessionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\SchoolRuleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardGatewayController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\AssessmentHubController;
use App\Http\Controllers\Student\AssessmentTakeController;
use App\Http\Controllers\Student\CourseVideoLibraryController;
use App\Http\Controllers\Student\LearningProgressController;
use App\Http\Controllers\Student\EvaluationController as StudentEvaluationController;
use App\Http\Controllers\OnlineClassroomController;
use App\Http\Controllers\Pwa\PwaController;
use App\Http\Controllers\Student\MediaUploadController;
use App\Http\Controllers\Student\SessionActionController;
use App\Http\Controllers\Teacher\CourseManageController as TeacherCourseManageController;
use App\Http\Controllers\Teacher\EvaluationController as TeacherEvaluationController;
use App\Http\Controllers\Teacher\AssessmentController as TeacherAssessmentController;
use App\Http\Controllers\Teacher\GradingController as TeacherGradingController;
use App\Http\Controllers\Teacher\ScheduleController;
use Illuminate\Support\Facades\Route;

// Public Website & Landing
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/du-hoc-han-quoc-2026', [LandingController::class, 'duHocHanQuoc2026'])->name('landing.duhoc2026');
Route::post('/du-hoc-han-quoc-2026/lead', [LandingController::class, 'duHocHanQuoc2026Lead'])->name('landing.duhoc2026.lead');

// SEO & Utilities
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/sitemap-pages.xml', [SeoController::class, 'sitemapPages'])->name('seo.sitemap.pages');
Route::get('/sitemap-posts.xml', [SeoController::class, 'sitemapPosts'])->name('seo.sitemap.posts');
Route::get('/sitemap-schools.xml', [SeoController::class, 'sitemapSchools'])->name('seo.sitemap.schools');
Route::get('/sitemap-events.xml', [SeoController::class, 'sitemapEvents'])->name('seo.sitemap.events');
Route::get('/rss', [RssController::class, 'index'])->name('rss');
Route::get('/rss.xml', [RssController::class, 'index']);
Route::get('/tim-kiem', [SearchController::class, 'index'])->name('search');
Route::post('/hotline-click', [HotlineController::class, 'click'])->name('hotline.click');
Route::get('/media/file/{id}/{name?}', [MediaFileController::class, 'file'])->name('media.file');
Route::get('/media/slider/{id}/{variant?}', [MediaFileController::class, 'sliderAsset'])->name('media.slider');

// Consult & Forms
Route::get('/tu-van/thong-bao', [ConsultController::class, 'notice'])->name('consult.notice');
Route::post('/tu-van', [ConsultController::class, 'submit'])->name('consult.submit');
Route::get('/form/{slug}', [PublicFormController::class, 'show'])->name('public.form.show');
Route::post('/form/{slug}', [PublicFormController::class, 'submit'])->name('public.form.submit');

// Public Comments
Route::post('/su-kien/{slug}/comment', [PublicCommentController::class, 'storeEvent'])->name('public.comment.event');
Route::post('/nganh/{slug}/comment', [PublicCommentController::class, 'storeMajor'])->name('public.comment.major');
Route::post('/truong/{category}/{slug}/comment', [PublicCommentController::class, 'storeSchool'])->name('public.comment.school');
Route::post('/truong/{slug}/comment', [PublicCommentController::class, 'storeSchoolLegacy'])->name('public.comment.school.legacy');
Route::post('/post/{slug}/comment', [PublicCommentController::class, 'store'])->name('public.comment.post');
Route::post('/{category}/{slug}/comment', [PublicCommentController::class, 'storeCategoryPost'])->name('public.comment.category');
Route::post('/{slug}/comment', [PublicCommentController::class, 'store'])->name('public.comment.general');

// Public Scoped Content Views
Route::get('/su-kien/{slug}', [PublicContentController::class, 'event'])->name('public.event');
Route::get('/nganh/{slug}', [PublicContentController::class, 'major'])->name('public.major');
Route::get('/truong/{categorySlug}/{postSlug}', [PublicContentController::class, 'school'])->name('public.school');
Route::get('/truong/{slug}', [PublicContentController::class, 'schoolLegacy'])->name('public.school.legacy');
Route::get('/post/{slug}', [PublicContentController::class, 'post'])->name('public.post');
Route::get('/tag/{slug}', [PublicContentController::class, 'tag'])->name('public.tag');
Route::get('/{categorySlug}/{postSlug}', [PublicContentController::class, 'categoryPost'])->name('public.category.post');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [LoginController::class, 'show'])->name('login');
    Route::post('/auth/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::prefix('admin')->group(function () {
    Route::get('/dang-nhap', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/dang-nhap', [AdminAuthController::class, 'login'])->name('admin.login.auth');
    Route::post('/dang-nhap/attempt', [AdminAuthController::class, 'login'])->name('admin.login.attempt');
    Route::match(['get', 'post'], '/dang-xuat', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('/ngon-ngu/{code}', [AdminLanguageController::class, 'set'])->name('admin.language.set');
});

Route::get('/app/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/app/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

Route::prefix('app')->name('pwa.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/dang-nhap', [PwaController::class, 'loginForm'])->name('login');
        Route::post('/dang-nhap', [PwaController::class, 'login'])->name('login.attempt');
    });

Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('pwa.home')
            : redirect()->route('pwa.login');
    })->name('entry');

    Route::middleware(['auth', 'pwa'])->group(function () {
        Route::get('/trang-chu', [PwaController::class, 'home'])->name('home');
        Route::get('/khoa-hoc', [PwaController::class, 'courses'])->name('courses');
        Route::get('/khoa-hoc/trinh-do/{level}', [PwaController::class, 'courseLevel'])->whereNumber('level')->name('courses.level');
        Route::get('/khoa-hoc/khoa/{course}', [PwaController::class, 'courseTeacher'])->whereNumber('course')->name('courses.course');
        Route::get('/khoa-hoc/buoi/{code}', [PwaController::class, 'courseSession'])->name('courses.session');
        Route::get('/zoom', [PwaController::class, 'zoom'])->name('zoom');
        Route::get('/kiem-tra', [PwaController::class, 'assessments'])->name('assessments');
        Route::get('/kiem-tra/{id}', [PwaController::class, 'assessmentShow'])->whereNumber('id')->name('assessments.show');
        Route::get('/ho-so', [PwaController::class, 'profile'])->name('profile');
        Route::get('/nhan-xet', [PwaController::class, 'evaluations'])->name('evaluations');
        Route::post('/vao-lop', [PwaController::class, 'join'])->name('join');
        Route::get('/lop/{id}', [PwaController::class, 'room'])->name('room');
        Route::match(['get', 'post'], '/lop/{id}/roi', [PwaController::class, 'leave'])->name('leave');
        Route::match(['get', 'post'], '/dang-xuat', [PwaController::class, 'logout'])->name('logout');
    });
});

Route::get('/lop-hoc-online/{id}/khach', [OnlineClassroomController::class, 'guestLobby'])->name('classroom.guest');
Route::post('/lop-hoc-online/{id}/khach/xin-vao', [OnlineClassroomController::class, 'guestRequestJoin'])->name('classroom.guest.request');

Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'], '/auth/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/ho-so-ca-nhan', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/ho-so-ca-nhan/luu', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/doi-mat-khau', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/doi-mat-khau/luu', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/dashboard', DashboardGatewayController::class)->name('dashboard');
    Route::get('/tong-quan', OverviewController::class)->name('overview');
    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/thong-bao/da-doc', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/thong-bao/da-doc-tat-ca', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/activity/pulse', ActivityPulseController::class)->name('activity.pulse');
    Route::get('/noi-quy', [SchoolRuleController::class, 'index'])->name('school-rules.show');
    Route::middleware('staff')->group(function () {
        Route::get('/noi-quy/sua', [SchoolRuleController::class, 'edit'])->name('school-rules.edit');
        Route::post('/noi-quy/luu', [SchoolRuleController::class, 'update'])->name('school-rules.update');
        Route::get('/noi-quy/xem-truoc', [SchoolRuleController::class, 'preview'])->name('school-rules.preview');
        Route::get('/lich-su-hoat-dong', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/lich-su-hoat-dong/check-diem-danh', [AttendanceCheckController::class, 'index'])->name('activity-attendance.index');
        Route::get('/lich-su-hoat-dong/check-diem-danh/{courseId}', [AttendanceCheckController::class, 'show'])->name('activity-attendance.show');
        Route::post('/lich-su-hoat-dong/check-diem-danh/{courseId}/buoi/{sessionId}/hoc-vien/{userId}', [AttendanceCheckController::class, 'toggle'])->name('activity-attendance.toggle');
        Route::post('/lich-su-hoat-dong/check-diem-danh/{courseId}/hoc-vien/{userId}/co-mat-tat-ca', [AttendanceCheckController::class, 'markAll'])->name('activity-attendance.mark-all');
        Route::get('/lich-su-hoat-dong/nhan-xet-giao-vien', [TeacherCommentsController::class, 'index'])->name('activity-comments.index');
        Route::get('/lich-su-hoat-dong/nhan-xet-hoc-vien', [StudentReflectionsController::class, 'index'])->name('activity-reflections.index');
        Route::get('/lich-su-hoat-dong/{id}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    });
    Route::get('/bai-kiem-tra', AssessmentHubController::class)->name('student.assessments');
    Route::get('/bai-kiem-tra/trang-thai', [TeacherAssessmentController::class, 'live'])->middleware('teacher')->name('teacher.assessments.live');
    Route::get('/tien-do-hoc-tap', LearningProgressController::class)->name('student.progress');
    Route::get('/thu-vien-video-khoa-hoc', CourseVideoLibraryController::class)->name('student.video-library');
    Route::get('/danh-gia-hoc-vien', StudentEvaluationController::class)->name('student.evaluations');
    Route::get('/lich-day', ScheduleController::class)->middleware('teacher')->name('teacher.schedule');
    Route::get('/giao-vien/danh-gia', [TeacherEvaluationController::class, 'index'])->middleware('teacher')->name('teacher.evaluations');
    Route::post('/giao-vien/danh-gia/luu', [TeacherEvaluationController::class, 'save'])->middleware('teacher')->name('teacher.evaluations.save');
    Route::get('/giao-vien/cham-diem', [TeacherGradingController::class, 'index'])->middleware('teacher')->name('teacher.grading');
    Route::post('/giao-vien/cham-diem/luu', [TeacherGradingController::class, 'save'])->middleware('teacher')->name('teacher.grading.save');
    Route::post('/giao-vien/cham-diem/upload-ghi-am', [TeacherGradingController::class, 'uploadRecording'])->middleware('teacher')->name('teacher.grading.upload');
    Route::post('/bai-kiem-tra/cham-diem', [TeacherAssessmentController::class, 'grade'])->middleware('teacher')->name('teacher.assessments.grade');
    Route::post('/bai-kiem-tra/cong-bo-ket-qua', [TeacherAssessmentController::class, 'publish'])->middleware('teacher')->name('teacher.assessments.publish');
    Route::post('/bai-kiem-tra/cho-lam-lai', [TeacherAssessmentController::class, 'grantRetake'])->middleware('teacher')->name('teacher.assessments.retake.grant');
    Route::post('/bai-kiem-tra/huy-lam-lai', [TeacherAssessmentController::class, 'revokeRetake'])->middleware('teacher')->name('teacher.assessments.retake.revoke');

    Route::prefix('lop-hoc-online')->name('classroom.')->group(function () {
        Route::get('/', [OnlineClassroomController::class, 'index'])->name('index');
        Route::get('/tao', [OnlineClassroomController::class, 'create'])->name('create');
        Route::post('/store', [OnlineClassroomController::class, 'store'])->name('store');
        Route::get('/{id}/phong-cho', [OnlineClassroomController::class, 'lobby'])->name('lobby');
        Route::post('/{id}/vao', [OnlineClassroomController::class, 'enter'])->name('enter');
        Route::get('/{id}/vao', [OnlineClassroomController::class, 'room'])->name('room');
        Route::post('/{id}/ping', [OnlineClassroomController::class, 'ping'])->name('ping');
        Route::get('/{id}/nguoi-tham-gia', [OnlineClassroomController::class, 'participants'])->name('participants');
        Route::match(['get', 'post'], '/{id}/roi-phong', [OnlineClassroomController::class, 'leave'])->name('leave');
        Route::get('/{id}/xin-vao/trang-thai', [OnlineClassroomController::class, 'joinRequestStatus'])->name('join-status');
        Route::post('/{id}/xin-vao/{requestId}/duyet', [OnlineClassroomController::class, 'approveJoinRequest'])->name('join.approve');
        Route::post('/{id}/xin-vao/{requestId}/tu-choi', [OnlineClassroomController::class, 'rejectJoinRequest'])->name('join.reject');
        Route::get('/{id}/jitsi-token', [OnlineClassroomController::class, 'jitsiToken'])->name('jitsi-token');
        Route::get('/{id}/cau-hoi', [OnlineClassroomController::class, 'polls'])->name('polls');
        Route::post('/{id}/cau-hoi/tao', [OnlineClassroomController::class, 'createPoll'])->name('polls.create');
        Route::post('/{id}/cau-hoi/{pollId}/tra-loi', [OnlineClassroomController::class, 'votePoll'])->name('polls.vote');
        Route::get('/{id}/cau-hoi/{pollId}/thong-ke', [OnlineClassroomController::class, 'pollStats'])->name('polls.stats');
        Route::post('/{id}/cau-hoi/{pollId}/dong', [OnlineClassroomController::class, 'closePoll'])->name('polls.close');
        Route::post('/{id}/cau-hoi-soan-san/{presetId}/phat', [OnlineClassroomController::class, 'launchPreset'])->name('polls.preset');
        Route::post('/{id}/chia-phong', [OnlineClassroomController::class, 'startBreakout'])->name('breakout.start');
        Route::post('/{id}/ket-thuc-chia-phong', [OnlineClassroomController::class, 'endBreakout'])->name('breakout.end');
        Route::post('/{id}/vao-phong-chia', [OnlineClassroomController::class, 'joinBreakout'])->name('breakout.join');
        Route::post('/{id}/ve-phong-chinh', [OnlineClassroomController::class, 'returnMain'])->name('breakout.main');
        Route::post('/{id}/xin-ra-ngoai', [OnlineClassroomController::class, 'breakOut'])->name('break-out');
        Route::post('/{id}/da-quay-lai', [OnlineClassroomController::class, 'breakReturn'])->name('break-return');
        Route::get('/{id}/video-link', [OnlineClassroomController::class, 'videoLink'])->name('video-link');
        Route::get('/{id}/video-ios', [OnlineClassroomController::class, 'videoIos'])->name('video-ios');
    });

    Route::middleware('teacher')->prefix('giao-vien/khoa-hoc')->name('teacher.course.')->group(function () {
        Route::get('/{id}', [TeacherCourseManageController::class, 'show'])->name('show');
        Route::get('/{id}/buoi/{sessionId}/hoc-vien/{userId}', [TeacherCourseManageController::class, 'student'])->name('student');
        Route::post('/{id}/buoi/{sessionId}/hoc-vien/{userId}/diem-danh', [TeacherCourseManageController::class, 'updateAttendance'])->name('attendance');
        Route::post('/{id}/buoi/{sessionId}/hoc-vien/{userId}/cham-tu-luan', [TeacherCourseManageController::class, 'saveEssay'])->name('essay');
    });

    Route::middleware('learner')->prefix('khoa-hoc-cua-toi')->group(function () {
        Route::post('/upload-anh-cau-tra-loi', [MediaUploadController::class, 'image'])->name('student.media.image');
        Route::post('/upload-ghi-am', [MediaUploadController::class, 'recording'])->name('student.media.recording');
        Route::get('/kiem-tra/{id}', [AssessmentTakeController::class, 'show'])->name('student.assessment.show');
        Route::post('/kiem-tra/{id}/bat-dau', [AssessmentTakeController::class, 'start'])->name('student.assessment.start');
        Route::post('/kiem-tra/{id}/luu-cau', [AssessmentTakeController::class, 'autosave'])->name('student.assessment.autosave');
        Route::post('/kiem-tra/{id}/nop', [AssessmentTakeController::class, 'submit'])->name('student.assessment.submit');
    });

    Route::middleware('learner')->prefix('khoa-hoc-cua-toi/buoi')->name('student.session.')->group(function () {
        Route::post('/{code}/vao', [SessionActionController::class, 'join'])->name('join');
        Route::post('/{code}/trac-nghiem-truoc', [SessionActionController::class, 'submitPreQuiz'])->name('pre.submit');
        Route::post('/{code}/trac-nghiem-truoc/luu-cau', [SessionActionController::class, 'autosavePreQuiz'])->name('pre.autosave');
        Route::post('/{code}/trac-nghiem-truoc/lam-lai', [SessionActionController::class, 'resetPreQuiz'])->name('pre.practice');
        Route::post('/{code}/trac-nghiem-truoc/ket-qua-da-nop', [SessionActionController::class, 'exitPreQuizPractice'])->name('pre.exit');
        Route::post('/{code}/trac-nghiem', [SessionActionController::class, 'submitPostQuiz'])->name('post.submit');
        Route::post('/{code}/trac-nghiem/luu-cau', [SessionActionController::class, 'autosavePostQuiz'])->name('post.autosave');
        Route::post('/{code}/trac-nghiem/lam-lai', [SessionActionController::class, 'resetPostQuiz'])->name('post.practice');
        Route::post('/{code}/trac-nghiem/ket-qua-da-nop', [SessionActionController::class, 'exitPostQuizPractice'])->name('post.exit');
        Route::post('/{code}/on-tap-kien-thuc/bat-dau', [SessionActionController::class, 'startKnowledgeReview'])->name('kr.start');
        Route::post('/{code}/on-tap-kien-thuc', [SessionActionController::class, 'submitKnowledgeReview'])->name('kr.submit');
        Route::post('/{code}/on-tap-kien-thuc/luu-cau', [SessionActionController::class, 'autosaveKnowledgeReview'])->name('kr.autosave');
        Route::post('/{code}/on-tap-kien-thuc/lam-lai', [SessionActionController::class, 'resetKnowledgeReview'])->name('kr.practice');
        Route::post('/{code}/on-tap-kien-thuc/ket-qua-da-nop', [SessionActionController::class, 'exitKnowledgeReviewPractice'])->name('kr.exit');
        Route::post('/{code}/nhan-xet', [SessionActionController::class, 'saveReflection'])->name('reflection');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // CMS Dynamic Modules: news, events, schools, majors
        foreach (['news', 'events', 'schools', 'majors'] as $mod) {
            Route::prefix($mod)->name($mod.'.')->group(function () use ($mod) {
                // Posts
                Route::get('/bai-viet', [ContentPostController::class, 'index'])->defaults('module', $mod)->name('posts.index');
                Route::get('/bai-viet/tao', [ContentPostController::class, 'create'])->defaults('module', $mod)->name('posts.create');
                Route::post('/bai-viet', [ContentPostController::class, 'store'])->defaults('module', $mod)->name('posts.store');
                Route::get('/bai-viet/{post}/sua', [ContentPostController::class, 'edit'])->defaults('module', $mod)->name('posts.edit');
                Route::match(['post', 'put'], '/bai-viet/{post}', [ContentPostController::class, 'update'])->defaults('module', $mod)->name('posts.update');
                Route::match(['post', 'delete'], '/bai-viet/{post}/xoa', [ContentPostController::class, 'destroy'])->defaults('module', $mod)->name('posts.destroy');

                // Categories
                Route::get('/chuyen-muc', [ContentCategoryController::class, 'index'])->defaults('module', $mod)->name('categories.index');
                Route::get('/chuyen-muc/tao', [ContentCategoryController::class, 'create'])->defaults('module', $mod)->name('categories.create');
                Route::post('/chuyen-muc', [ContentCategoryController::class, 'store'])->defaults('module', $mod)->name('categories.store');
                Route::get('/chuyen-muc/{category}/sua', [ContentCategoryController::class, 'edit'])->defaults('module', $mod)->name('categories.edit');
                Route::match(['post', 'put'], '/chuyen-muc/{category}', [ContentCategoryController::class, 'update'])->defaults('module', $mod)->name('categories.update');
                Route::match(['post', 'delete'], '/chuyen-muc/{category}/xoa', [ContentCategoryController::class, 'destroy'])->defaults('module', $mod)->name('categories.destroy');
                Route::post('/chuyen-muc/{category}/len', [ContentCategoryController::class, 'moveUp'])->defaults('module', $mod)->name('categories.up');
                Route::post('/chuyen-muc/{category}/xuong', [ContentCategoryController::class, 'moveDown'])->defaults('module', $mod)->name('categories.down');

                // Tags
                Route::get('/tags', [ContentTagController::class, 'index'])->defaults('module', $mod)->name('tags.index');
                Route::get('/tags/tao', [ContentTagController::class, 'create'])->defaults('module', $mod)->name('tags.create');
                Route::post('/tags', [ContentTagController::class, 'store'])->defaults('module', $mod)->name('tags.store');
                Route::get('/tags/{tag}/sua', [ContentTagController::class, 'edit'])->defaults('module', $mod)->name('tags.edit');
                Route::match(['post', 'put'], '/tags/{tag}', [ContentTagController::class, 'update'])->defaults('module', $mod)->name('tags.update');
                Route::match(['post', 'delete'], '/tags/{tag}/xoa', [ContentTagController::class, 'destroy'])->defaults('module', $mod)->name('tags.destroy');
                Route::post('/tags/{tag}/len', [ContentTagController::class, 'moveUp'])->defaults('module', $mod)->name('tags.up');
                Route::post('/tags/{tag}/xuong', [ContentTagController::class, 'moveDown'])->defaults('module', $mod)->name('tags.down');
            });
        }

        // Site Pages
        Route::prefix('trang-tinh')->name('site-pages.')->group(function () {
            Route::get('/', [SitePageController::class, 'index'])->name('index');
            Route::get('/tao', [SitePageController::class, 'create'])->name('create');
            Route::post('/', [SitePageController::class, 'store'])->name('store');
            Route::get('/{id}/sua', [SitePageController::class, 'edit'])->name('edit');
            Route::match(['post', 'put'], '/{id}', [SitePageController::class, 'update'])->name('update');
            Route::match(['post', 'delete'], '/{id}/xoa', [SitePageController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/dat-trang-chu', [SitePageController::class, 'setHomepage'])->name('homepage');
        });

        // Site Blocks
        Route::prefix('blocks')->name('blocks.')->group(function () {
            Route::get('/', [SiteBlockController::class, 'index'])->name('index');
            Route::get('/tao', [SiteBlockController::class, 'create'])->name('create');
            Route::post('/', [SiteBlockController::class, 'store'])->name('store');
            Route::get('/{id}/sua', [SiteBlockController::class, 'edit'])->name('edit');
            Route::match(['post', 'put'], '/{id}', [SiteBlockController::class, 'update'])->name('update');
            Route::match(['post', 'delete'], '/{id}/xoa', [SiteBlockController::class, 'destroy'])->name('destroy');
        });

        // Templates
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [ContentTemplateController::class, 'index'])->name('index');
            Route::post('/', [ContentTemplateController::class, 'store'])->name('store');
            Route::get('/{id}/sua', [ContentTemplateController::class, 'edit'])->name('edit');
            Route::match(['post', 'put'], '/{id}', [ContentTemplateController::class, 'update'])->name('update');
            Route::post('/{id}/kich-hoat', [ContentTemplateController::class, 'setActive'])->name('activate');
        });

        // Sliders
        Route::prefix('slider')->name('slider.')->group(function () {
            Route::get('/', [SliderController::class, 'index'])->name('index');
            Route::post('/chuyen-muc', [SliderController::class, 'storeCategory'])->name('categories.store');
            Route::match(['post', 'put'], '/chuyen-muc/{id}', [SliderController::class, 'updateCategory'])->name('categories.update');
            Route::match(['post', 'delete'], '/chuyen-muc/{id}/xoa', [SliderController::class, 'destroyCategory'])->name('categories.destroy');
            Route::get('/{category}/slides', [SliderController::class, 'slides'])->name('slides');
            Route::post('/{category}/slides', [SliderController::class, 'storeSlide'])->name('slides.store');
            Route::match(['post', 'put'], '/{category}/slides/{slide}', [SliderController::class, 'updateSlide'])->name('slides.update');
            Route::match(['post', 'delete'], '/{category}/slides/{slide}/xoa', [SliderController::class, 'destroySlide'])->name('slides.destroy');
            Route::post('/{category}/slides/{slide}/len', [SliderController::class, 'moveSlideUp'])->name('slides.up');
            Route::post('/{category}/slides/{slide}/xuong', [SliderController::class, 'moveSlideDown'])->name('slides.down');
        });

        // Media
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [SiteMediaController::class, 'index'])->name('index');
            Route::get('/json', [SiteMediaController::class, 'json'])->name('json');
            Route::post('/upload', [SiteMediaController::class, 'store'])->name('store');
            Route::post('/from-url', [SiteMediaController::class, 'storeFromUrl'])->name('store-url');
            Route::match(['post', 'delete'], '/{id}/xoa', [SiteMediaController::class, 'destroy'])->name('destroy');
        });

        // Comments
        Route::prefix('binh-luan')->name('comments.')->group(function () {
            Route::get('/', [PostCommentController::class, 'index'])->name('index');
            Route::post('/{id}/duyet', function ($id) {
                return app(PostCommentController::class)->setStatus($id, 'approved');
            })->name('approve');
            Route::post('/{id}/spam', function ($id) {
                return app(PostCommentController::class)->setStatus($id, 'spam');
            })->name('spam');
            Route::match(['post', 'delete'], '/{id}/xoa', [PostCommentController::class, 'destroy'])->name('destroy');
        });

        // FAQ
        Route::prefix('faq')->name('faq.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::post('/chuyen-muc', [FaqController::class, 'storeCategory'])->name('categories.store');
            Route::match(['post', 'put'], '/chuyen-muc/{id}', [FaqController::class, 'updateCategory'])->name('categories.update');
            Route::match(['post', 'delete'], '/chuyen-muc/{id}/xoa', [FaqController::class, 'destroyCategory'])->name('categories.destroy');
            Route::get('/{category}/items', [FaqController::class, 'items'])->name('items');
            Route::post('/{category}/items', [FaqController::class, 'storeItem'])->name('items.store');
            Route::match(['post', 'put'], '/{category}/items/{item}', [FaqController::class, 'updateItem'])->name('items.update');
            Route::match(['post', 'delete'], '/{category}/items/{item}/xoa', [FaqController::class, 'destroyItem'])->name('items.destroy');
            Route::post('/{category}/items/{item}/len', [FaqController::class, 'moveItemUp'])->name('items.up');
            Route::post('/{category}/items/{item}/xuong', [FaqController::class, 'moveItemDown'])->name('items.down');
        });

        // Custom Forms
        Route::prefix('forms')->name('forms.')->group(function () {
            Route::get('/', [CustomFormController::class, 'index'])->name('index');
            Route::get('/tao', [CustomFormController::class, 'create'])->name('create');
            Route::post('/', [CustomFormController::class, 'store'])->name('store');
            Route::get('/{id}/sua', [CustomFormController::class, 'edit'])->name('edit');
            Route::match(['post', 'put'], '/{id}', [CustomFormController::class, 'update'])->name('update');
            Route::match(['post', 'delete'], '/{id}/xoa', [CustomFormController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/fields', [CustomFormController::class, 'fields'])->name('fields');
            Route::post('/{id}/fields', [CustomFormController::class, 'storeField'])->name('fields.store');
            Route::match(['post', 'put'], '/{id}/fields/{fieldId}', [CustomFormController::class, 'updateField'])->name('fields.update');
            Route::match(['post', 'delete'], '/{id}/fields/{fieldId}/xoa', [CustomFormController::class, 'destroyField'])->name('fields.destroy');
            Route::post('/{id}/fields/{fieldId}/len', [CustomFormController::class, 'moveFieldUp'])->name('fields.up');
            Route::post('/{id}/fields/{fieldId}/xuong', [CustomFormController::class, 'moveFieldDown'])->name('fields.down');
            Route::get('/{id}/submissions', [CustomFormController::class, 'submissions'])->name('submissions');
            Route::get('/{id}/submissions/export', [CustomFormController::class, 'exportSubmissions'])->name('submissions.export');
        });

        // Consult Leads
        Route::prefix('lien-he-tu-van')->name('consult-leads.')->group(function () {
            Route::get('/', [ConsultLeadController::class, 'index'])->name('index');
            Route::get('/export', [ConsultLeadController::class, 'export'])->name('export');
            Route::get('/{id}', [ConsultLeadController::class, 'show'])->name('show');
            Route::match(['post', 'delete'], '/{id}/xoa', [ConsultLeadController::class, 'destroy'])->name('destroy');
        });

        // Analytics
        Route::prefix('bao-cao-thong-ke')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])->name('index');
            Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
            Route::get('/chi-tiet', [AnalyticsController::class, 'detail'])->name('detail');
        });

        // Site Settings
        Route::prefix('cai-dat-he-thong')->name('settings.')->group(function () {
            Route::get('/', [SiteSettingsController::class, 'edit'])->name('edit');
            Route::post('/luu', [SiteSettingsController::class, 'update'])->name('update');
        });

        // Site Customize (Identity & Topbar)
        Route::prefix('nhan-dien')->name('customize.')->group(function () {
            Route::get('/', [SiteCustomizeController::class, 'identity'])->name('identity');
            Route::post('/luu', [SiteCustomizeController::class, 'saveIdentity'])->name('identity.save');
            Route::get('/topbar', [SiteCustomizeController::class, 'topbar'])->name('topbar');
            Route::post('/topbar/luu', [SiteCustomizeController::class, 'saveTopbar'])->name('topbar.save');
        });

        // Frontend Appearance
        Route::prefix('giao-dien-blade')->name('appearance.')->group(function () {
            Route::get('/', [FrontendAppearanceController::class, 'edit'])->name('edit');
            Route::post('/luu', [FrontendAppearanceController::class, 'update'])->name('update');
        });

        // Navigation Menus
        Route::prefix('menu')->name('menus.')->group(function () {
            Route::get('/', [NavMenuController::class, 'index'])->name('index');
            Route::post('/', [NavMenuController::class, 'store'])->name('store');
            Route::match(['post', 'put'], '/{id}', [NavMenuController::class, 'update'])->name('update');
            Route::match(['post', 'delete'], '/{id}/xoa', [NavMenuController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/builder', [NavMenuController::class, 'builder'])->name('builder');
            Route::post('/{id}/items', [NavMenuController::class, 'storeItem'])->name('items.store');
            Route::match(['post', 'put'], '/{id}/items/{itemId}', [NavMenuController::class, 'updateItem'])->name('items.update');
            Route::match(['post', 'delete'], '/{id}/items/{itemId}/xoa', [NavMenuController::class, 'destroyItem'])->name('items.destroy');
            Route::post('/{id}/tree', [NavMenuController::class, 'saveTree'])->name('tree');
        });

        // Admin Profile & UI Settings
        Route::prefix('ho-so')->name('profile.')->group(function () {
            Route::get('/', [AdminProfileController::class, 'edit'])->name('edit');
            Route::post('/luu', [AdminProfileController::class, 'update'])->name('update');
        });
        Route::post('/cai-dat-giao-dien', [AdminUiSettingsController::class, 'update'])->name('admin-settings.update');

        // CMS Embeds
        Route::prefix('embeds')->name('embeds.')->group(function () {
            Route::get('/blocks', [CmsEmbedController::class, 'blocks'])->name('blocks');
            Route::get('/faqs', [CmsEmbedController::class, 'faqs'])->name('faqs');
            Route::get('/sliders', [CmsEmbedController::class, 'sliders'])->name('sliders');
        });

        // SQL Updates
        Route::prefix('csdl-nang-cap')->name('sql.')->group(function () {
            Route::get('/', [SchemaMigrateController::class, 'index'])->name('index');
            Route::post('/chay', [SchemaMigrateController::class, 'run'])->name('run');
        });
    });

    Route::middleware('admin')->prefix('quan-ly-lop-hoc')->name('admin.classrooms.')->group(function () {
        Route::get('/', [ClassroomController::class, 'index'])->name('index');
        Route::get('/tao', [ClassroomController::class, 'create'])->name('create');
        Route::post('/store', [ClassroomController::class, 'store'])->name('store');
        Route::get('/sua/{id}', [ClassroomController::class, 'edit'])->name('edit');
        Route::post('/update/{id}', [ClassroomController::class, 'update'])->name('update');
        Route::post('/delete/{id}', [ClassroomController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('admin')->prefix('cap-nhat-csdl')->name('admin.database.')->group(function () {
        Route::get('/', [DatabaseMaintenanceController::class, 'index'])->name('index');
        Route::post('/longtext-cau-hoi', [DatabaseMaintenanceController::class, 'expandQuestionText'])->name('expand-question-text');
    });

    Route::middleware('admin')->prefix('quan-ly-voucher')->name('admin.vouchers.')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::post('/tao', [VoucherController::class, 'store'])->name('store');
        Route::get('/kiem-tra', [VoucherController::class, 'checkPage'])->name('check');
        Route::post('/check-ajax', [VoucherController::class, 'checkAjax'])->name('check.ajax');
        Route::post('/ap-dung', [VoucherController::class, 'redeem'])->name('redeem');
        Route::post('/{voucher}/tao-lai-anh', [VoucherController::class, 'regenerate'])->name('regenerate');
        Route::post('/{voucher}/xoa', [VoucherController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('staff')->prefix('kiem-tra-voucher')->name('staff.vouchers.')->group(function () {
        Route::get('/', [VoucherController::class, 'checkPage'])->name('check');
        Route::post('/check-ajax', [VoucherController::class, 'checkAjax'])->name('check.ajax');
        Route::post('/ap-dung', [VoucherController::class, 'redeem'])->name('redeem');
    });

    Route::middleware('admin')->prefix('nguoi-dung')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/store', [UserController::class, 'store'])->name('store');
        Route::post('/update/{id}', [UserController::class, 'update'])->name('update');
        Route::post('/delete/{id}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/trang-thai', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::middleware('admin')->prefix('khoa-hoc')->name('admin.courses.')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('index');
        Route::get('/tao', [CourseController::class, 'create'])->name('create');
        Route::post('/store', [CourseController::class, 'store'])->name('store');
        Route::post('/xoa-khong-thanh-vien', [CourseController::class, 'destroyEmpty'])->name('destroy-empty');
        Route::get('/{id}', [CourseController::class, 'show'])->name('show');
        Route::post('/{id}/luu', [CourseController::class, 'update'])->name('update');
        Route::post('/{id}/huy', [CourseController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/xoa', [CourseController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/hoc-vien/tim', [CourseMemberController::class, 'search'])->name('members.search');
        Route::post('/{id}/hoc-vien/them', [CourseMemberController::class, 'store'])->name('members.store');
        Route::post('/{id}/hoc-vien/{userId}/hoan-thanh', [CourseMemberController::class, 'complete'])->name('members.complete');
        Route::post('/{id}/hoc-vien/{userId}/chuyen-khoa', [CourseMemberController::class, 'transfer'])->name('members.transfer');
        Route::post('/{id}/hoc-vien/{userId}/go', [CourseMemberController::class, 'destroy'])->name('members.destroy');
        Route::post('/{id}/thoi-gian-hoc/luu', [CourseScheduleController::class, 'save'])->name('schedule.save');
        Route::get('/{id}/thoi-gian-hoc/buoi', [CourseScheduleController::class, 'sessionRows'])->name('schedule.sessions');
        Route::post('/{id}/thoi-gian-hoc/lui-lich', [CourseScheduleController::class, 'shift'])->name('schedule.shift');
        Route::post('/{id}/thoi-gian-hoc/buoi/{sessionId}/luu', [CourseScheduleController::class, 'updateSession'])->name('schedule.session.update');
        Route::post('/{id}/thoi-gian-hoc/bai-kiem-tra/{assessmentId}/luu', [CourseScheduleController::class, 'updateAssessment'])->name('schedule.assessment.update');
        Route::post('/{id}/thoi-gian-hoc/bai-kiem-tra/{assessmentId}/khung-gio', [CourseScheduleController::class, 'storeAssessmentSlot'])->name('schedule.assessmentSlots.store');
        Route::post('/{id}/thoi-gian-hoc/bai-kiem-tra/{assessmentId}/khung-gio/{slotId}/luu', [CourseScheduleController::class, 'updateAssessmentSlot'])->name('schedule.assessmentSlots.update');
        Route::post('/{id}/thoi-gian-hoc/bai-kiem-tra/{assessmentId}/khung-gio/{slotId}/xoa', [CourseScheduleController::class, 'destroyAssessmentSlot'])->name('schedule.assessmentSlots.destroy');
    });

    Route::middleware('admin')->prefix('chuong-trinh-hoc')->name('admin.programs.')->group(function () {
        Route::get('/', [StudyProgramController::class, 'index'])->name('index');
        Route::post('/store', [StudyProgramController::class, 'store'])->name('store');
        Route::post('/{id}/sao-chep', [StudyProgramController::class, 'copy'])->name('copy');
        Route::get('/{id}', [StudyProgramController::class, 'show'])->name('show');
        Route::post('/{id}/update', [StudyProgramController::class, 'update'])->name('update');
        Route::post('/{id}/luu-tru', [StudyProgramController::class, 'archive'])->name('archive');
        Route::post('/{id}/cap-nhat-lai', [StudyProgramController::class, 'refresh'])->name('refresh');
        Route::post('/{id}/trinh-do', [StudyProgramController::class, 'saveLevel'])->name('levels.save');
        Route::post('/{id}/trinh-do/sap-xep', [StudyProgramController::class, 'reorderLevels'])->name('levels.reorder');
        Route::post('/{id}/trinh-do/{levelId}/xoa', [StudyProgramController::class, 'deleteLevel'])->name('levels.destroy');
        Route::post('/{id}/buoi-hoc', [StudyProgramController::class, 'saveSession'])->name('sessions.save');
        Route::post('/{id}/buoi-hoc/sap-xep', [StudyProgramController::class, 'reorderSessions'])->name('sessions.reorder');
        Route::post('/{id}/buoi-hoc/{sessionId}/xoa', [StudyProgramController::class, 'deleteSession'])->name('sessions.destroy');
        Route::get('/{id}/buoi-hoc/{sessionId}', [StudyProgramSessionController::class, 'show'])->name('sessions.show');
        Route::post('/{id}/buoi-hoc/{sessionId}/luu', [StudyProgramSessionController::class, 'update'])->name('sessions.update');
        Route::post('/{id}/buoi-hoc/{sessionId}/upload-anh-cap', [StudyProgramSessionController::class, 'uploadPairImage'])->name('sessions.upload.image');
        Route::post('/{id}/buoi-hoc/{sessionId}/upload-mp3-nghe', [StudyProgramSessionController::class, 'uploadListeningAudio'])->name('sessions.upload.audio');
        Route::post('/{id}/buoi-hoc/{sessionId}/upload-video-truoc-buoi', [StudyProgramSessionController::class, 'uploadPreSessionVideo'])->name('sessions.upload.video');
        Route::get('/{id}/buoi-hoc/{sessionId}/cau-hoi/{groupSlug}/tao/{typeSlug?}', [StudyProgramSessionController::class, 'createQuestion'])->name('sessions.questions.create');
        Route::get('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}', [StudyProgramSessionController::class, 'editQuestion'])->name('sessions.questions.edit');
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/sap-xep', [StudyProgramSessionController::class, 'reorderQuestions'])->name('sessions.questions.reorder');
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}/luu', [StudyProgramSessionController::class, 'updateQuestion'])->name('sessions.questions.update');
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}/nhan-ban', [StudyProgramSessionController::class, 'cloneQuestion'])->name('sessions.questions.clone');
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}/luu-tu-dong', [StudyProgramSessionController::class, 'autosaveQuestion'])->name('sessions.questions.autosave');
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}/xoa', [StudyProgramSessionController::class, 'deleteQuestion'])->name('sessions.questions.destroy');

        Route::post('/{id}/bai-kiem-tra', [StudyProgramAssessmentController::class, 'store'])->name('assessments.store');
        Route::post('/{id}/bai-kiem-tra/sap-xep', [StudyProgramAssessmentController::class, 'reorder'])->name('assessments.reorder');
        Route::get('/{id}/bai-kiem-tra/{assessmentId}', [StudyProgramAssessmentController::class, 'show'])->name('assessments.show');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/luu', [StudyProgramAssessmentController::class, 'update'])->name('assessments.update');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/chia-deu-diem', [StudyProgramAssessmentController::class, 'equalizePoints'])->name('assessments.equalize-points');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/xoa', [StudyProgramAssessmentController::class, 'destroy'])->name('assessments.destroy');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/upload-anh-cap', [StudyProgramAssessmentController::class, 'uploadPairImage'])->name('assessments.upload.image');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/upload-mp3-nghe', [StudyProgramAssessmentController::class, 'uploadListeningAudio'])->name('assessments.upload.audio');
        Route::get('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/tao/{typeSlug?}', [StudyProgramAssessmentController::class, 'createQuestion'])->name('assessments.questions.create');
        Route::get('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}', [StudyProgramAssessmentController::class, 'editQuestion'])->name('assessments.questions.edit');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/sap-xep', [StudyProgramAssessmentController::class, 'reorderQuestions'])->name('assessments.questions.reorder');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}/luu', [StudyProgramAssessmentController::class, 'updateQuestion'])->name('assessments.questions.update');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}/nhan-ban', [StudyProgramAssessmentController::class, 'cloneQuestion'])->name('assessments.questions.clone');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}/luu-tu-dong', [StudyProgramAssessmentController::class, 'autosaveQuestion'])->name('assessments.questions.autosave');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}/xoa', [StudyProgramAssessmentController::class, 'deleteQuestion'])->name('assessments.questions.destroy');

        Route::post('/{id}/on-tap-kien-thuc', [StudyProgramKnowledgeReviewController::class, 'store'])->name('reviews.store');
        Route::post('/{id}/on-tap-kien-thuc/sap-xep', [StudyProgramKnowledgeReviewController::class, 'reorder'])->name('reviews.reorder');
        Route::get('/{id}/on-tap-kien-thuc/{reviewId}', [StudyProgramKnowledgeReviewController::class, 'show'])->name('reviews.show');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/luu', [StudyProgramKnowledgeReviewController::class, 'update'])->name('reviews.update');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/xoa', [StudyProgramKnowledgeReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/upload-anh-cap', [StudyProgramKnowledgeReviewController::class, 'uploadPairImage'])->name('reviews.upload.image');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/upload-mp3-nghe', [StudyProgramKnowledgeReviewController::class, 'uploadListeningAudio'])->name('reviews.upload.audio');
        Route::get('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/tao/{typeSlug?}', [StudyProgramKnowledgeReviewController::class, 'createQuestion'])->name('reviews.questions.create');
        Route::get('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}', [StudyProgramKnowledgeReviewController::class, 'editQuestion'])->name('reviews.questions.edit');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/sap-xep', [StudyProgramKnowledgeReviewController::class, 'reorderQuestions'])->name('reviews.questions.reorder');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}/luu', [StudyProgramKnowledgeReviewController::class, 'updateQuestion'])->name('reviews.questions.update');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}/nhan-ban', [StudyProgramKnowledgeReviewController::class, 'cloneQuestion'])->name('reviews.questions.clone');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}/luu-tu-dong', [StudyProgramKnowledgeReviewController::class, 'autosaveQuestion'])->name('reviews.questions.autosave');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}/xoa', [StudyProgramKnowledgeReviewController::class, 'deleteQuestion'])->name('reviews.questions.destroy');
    });
});

// Catch-all Public Page/Category Route (Must remain at the very end of web.php)
Route::get('/{slug}', [PublicContentController::class, 'slug'])->name('public.slug');
