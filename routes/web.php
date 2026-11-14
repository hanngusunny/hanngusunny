<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceCheckController;
use App\Http\Controllers\TeacherCommentsController;
use App\Http\Controllers\StudentReflectionsController;
use App\Http\Controllers\ActivityPulseController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DatabaseMaintenanceController;
use App\Http\Controllers\Admin\CourseMemberController;
use App\Http\Controllers\Admin\CourseScheduleController;
use App\Http\Controllers\Admin\StudyProgramAssessmentController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Admin\StudyProgramKnowledgeReviewController;
use App\Http\Controllers\Admin\StudyProgramSessionController;
use App\Http\Controllers\Admin\UserController;
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

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'show'])->name('login');
    Route::get('/dang-nhap', [LoginController::class, 'show'])->name('login.alias');
    Route::post('/auth/login', [LoginController::class, 'login'])->name('login.attempt');
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
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}/luu-tu-dong', [StudyProgramSessionController::class, 'autosaveQuestion'])->name('sessions.questions.autosave');
        Route::post('/{id}/buoi-hoc/{sessionId}/cau-hoi/{questionId}/xoa', [StudyProgramSessionController::class, 'deleteQuestion'])->name('sessions.questions.destroy');

        Route::post('/{id}/bai-kiem-tra', [StudyProgramAssessmentController::class, 'store'])->name('assessments.store');
        Route::post('/{id}/bai-kiem-tra/sap-xep', [StudyProgramAssessmentController::class, 'reorder'])->name('assessments.reorder');
        Route::get('/{id}/bai-kiem-tra/{assessmentId}', [StudyProgramAssessmentController::class, 'show'])->name('assessments.show');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/luu', [StudyProgramAssessmentController::class, 'update'])->name('assessments.update');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/xoa', [StudyProgramAssessmentController::class, 'destroy'])->name('assessments.destroy');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/upload-anh-cap', [StudyProgramAssessmentController::class, 'uploadPairImage'])->name('assessments.upload.image');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/upload-mp3-nghe', [StudyProgramAssessmentController::class, 'uploadListeningAudio'])->name('assessments.upload.audio');
        Route::get('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/tao/{typeSlug?}', [StudyProgramAssessmentController::class, 'createQuestion'])->name('assessments.questions.create');
        Route::get('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}', [StudyProgramAssessmentController::class, 'editQuestion'])->name('assessments.questions.edit');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/sap-xep', [StudyProgramAssessmentController::class, 'reorderQuestions'])->name('assessments.questions.reorder');
        Route::post('/{id}/bai-kiem-tra/{assessmentId}/cau-hoi/{questionId}/luu', [StudyProgramAssessmentController::class, 'updateQuestion'])->name('assessments.questions.update');
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
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}/luu-tu-dong', [StudyProgramKnowledgeReviewController::class, 'autosaveQuestion'])->name('reviews.questions.autosave');
        Route::post('/{id}/on-tap-kien-thuc/{reviewId}/cau-hoi/{questionId}/xoa', [StudyProgramKnowledgeReviewController::class, 'deleteQuestion'])->name('reviews.questions.destroy');
    });
});
