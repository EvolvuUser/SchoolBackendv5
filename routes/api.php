    <?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\RoleController;
    use App\Http\Controllers\AdminController;
    use App\Http\Controllers\LoginController;
    use App\Http\Controllers\AssessmentController;
    use App\Http\Controllers\NewController;
    use App\Http\Controllers\CertificateController;
    use App\Http\Controllers\NoticeController;
    use App\Http\Controllers\SubstituteTeacher;
    use App\Http\Controllers\StudentController;
    use App\Http\Controllers\HscController;
    use App\Http\Controllers\ReportController;
    use App\Http\Services\SmartMailer;

    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

   // Protected routes
    Route::middleware(['jwt.auth'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('sessionData', [AuthController::class, 'getUserDetails']);
        Route::post('update_academic_year', [AuthController::class, 'updateAcademicYear']);







        // Route::get('/getAuthUser', [AdminController::class, 'getAuthUser']);
        // Route::put('/updateauthacademicyear', [AdminController::class, 'updateAcademicYearForAuthUser']);
        // Route::get('/someControllerMethod', [LoginController::class, 'someControllerMethod']);

        
        // Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        // Route::get('/session-data', [LoginController::class, 'getSessionData']);
        Route::get('/getAcademicyear', [LoginController::class, 'getAcademicyear']);
        // Route::put('/updateAcademicYear', [LoginController::class, 'updateAcademicYear']);
        Route::post('/clearData', [LoginController::class, 'clearData'])->name('clearData');
        Route::put('/update_password', [LoginController::class, 'updatePassword']);
        Route::get('/editprofile', [AuthController::class, 'editUser']);
        Route::put('/update_profile', [AuthController::class, 'updateUser']);

        //Master and its sub module routes  Module Routes 
        //Section model Routes 
        Route::post('/check_section_name', [AdminController::class, 'checkSectionName']);
        Route::get('/sections', [AdminController::class, 'listSections']);
        Route::post('/sections', [AdminController::class, 'storeSection']);
        Route::get('/sections/{id}/edit', [AdminController::class, 'editSection']);
        Route::put('/sections/{id}', [AdminController::class, 'updateSection']);
        Route::delete('/sections/{id}', [AdminController::class, 'deleteSection']);

        //Classes Module Route  
        Route::post('/check_class_name', [AdminController::class, 'checkClassName']);
        Route::get('/classes', [AdminController::class, 'getClass']);
        Route::post('/classes', [AdminController::class, 'storeClass']);
        Route::get('/classes/{id}', [AdminController::class, 'showClass']);
        Route::put('/classes/{id}', [AdminController::class, 'updateClass']);
        Route::delete('/classes/{id}', [AdminController::class, 'destroyClass']);

        // Division Module Routes 
        Route::post('/check_division_name', [AdminController::class, 'checkDivisionName']);
        Route::get('/getDivision', [AdminController::class, 'getDivision']);
        Route::get('/get_class_for_division', [AdminController::class, 'getClassforDivision']);
        Route::post('/store_division', [AdminController::class, 'storeDivision']);
        Route::get('/getDivision/{id}', [AdminController::class, 'showDivision']);
        Route::put('/getDivision/{id}', [AdminController::class, 'updateDivision']);
        Route::delete('/getDivision/{id}', [AdminController::class, 'destroyDivision']);

        // Dashboard API   
        Route::get('/studentss', [AdminController::class, 'getStudentData']);
        Route::get('/staff', [AdminController::class, 'staff']);
        Route::get('/getbirthday', [AdminController::class, 'getbirthday']);
        Route::get('/events', [AdminController::class, 'getEvents']);
        Route::get('/parent-notices', [AdminController::class, 'getParentNotices']);
        Route::get('/staff-notices', [AdminController::class, 'getNoticesForTeachers']);
        Route::get('/getClassDivisionTotalStudents', [AdminController::class, 'getClassDivisionTotalStudents']);
        Route::get('/getHouseViseStudent', [AdminController::class, 'getHouseViseStudent']);
        Route::get('/staffbirthdaycount', [AdminController::class, 'staffBirthdaycount']);
        Route::get('/staffbirthdaylist', [AdminController::class, 'staffBirthdayList']);
        Route::get('/send_teacher_birthday_email', [AdminController::class, 'sendTeacherBirthdayEmail']);
        Route::get('/ticketcount', [AdminController::class, 'ticketCount']);
        Route::get('/ticketlist', [AdminController::class, 'getTicketList']);
        Route::get('/feecollection', [AdminController::class, 'feeCollection']);
        // Route::get('/fee_collection_list', [AdminController::class, 'feeCollectionList']);
        Route::get('/get_bank_accountName', [AdminController::class, 'getBankAccountName']);  
        Route::get('/getAcademicYear', [AdminController::class, 'getAcademicYears']);
        Route::get('/fee_collection_list', [AdminController::class, 'pendingCollectedFeeData']);
        // Route::get('/pending_collected_fee_data_list', [AdminController::class, 'pendingCollectedFeeDatalist']);
        Route::get('/collected_fee_list', [AdminController::class, 'collectedFeeList']);


        // Staff Module API 
        Route::get('/staff_list', [AdminController::class, 'getStaffList']);
        Route::post('/store_staff', [AdminController::class, 'storeStaff']);
        Route::get('/teachers/{id}', [AdminController::class, 'editStaff']);
        Route::put('/teachers/{id}', [AdminController::class, 'updateStaff']);
        Route::delete('/teachers/{id}', [AdminController::class, 'deleteStaff']);

        // Roles Routes 
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'delete'])->name('roles.delete');

        //Showing Roles with the Permissions   showRoles
        Route::get('/show_roles', [RoleController::class, 'showRoles']);
        Route::get('/show_access/{roleId}', [RoleController::class, 'showAccess']);
        Route::post('/update_access/{roleId}', [RoleController::class, 'updateAccess']);
        Route::get('/navmenulist', [RoleController::class, 'navMenulist']);
        Route::get('/navmenulisttest', [RoleController::class, 'navMenulisttest']);        


        // Menus Model Routes 
        Route::get('/menus', [RoleController::class, 'getMenus']);
        Route::post('/menus', [RoleController::class, 'storeMenus']);
        Route::get('/menus/{id}', [RoleController::class, 'showMenus']);
        Route::put('/menus/{id}', [RoleController::class, 'updateMenus']);
        Route::delete('/menus/{id}', [RoleController::class, 'destroy']);

        // API for the subject master.
        Route::post('/check_subject_name', [AdminController::class, 'checkSubjectName']);
        Route::get('/subject', [AdminController::class, 'getSubjects']);
        Route::post('/subject', [AdminController::class, 'storeSubject']);
        Route::get('/subject/{id}', [AdminController::class, 'editSubject']);
        Route::put('/subject/{id}', [AdminController::class, 'updateSubject']);
        Route::delete('/subject/{id}', [AdminController::class, 'deleteSubject']);     
       

        // Subject Allotment Manage Tab 
        Route::get('/getClassList', [AdminController::class, 'getClassList']);//done  //list the class 
        Route::get('/divisions-and-subjects/{class_id}', [AdminController::class, 'getDivisionsAndSubjects']);//  done list the division and subject by selected class,    
        Route::get('/get_class_section', [AdminController::class, 'getallClass']); //Done  list the class name with the division
        Route::get('/get_subject_Alloted', [AdminController::class, 'getSubjectAlloted']); //Done  list the subject allotment base on the selected section_id
        Route::get('/get_subject_Alloted/{subjectId}', [AdminController::class, 'editSubjectAllotment']);//Done    return the object of subject with associated details for the selected subject
        Route::put('/update_subject_Alloted/{subjectId}', [AdminController::class, 'updateSubjectAllotment']);//Done  update 
        Route::delete('/delete_subject_Alloted/{subjectId}', [AdminController::class, 'deleteSubjectAllotment']);// Done  delete 
         
        // Allot Subjects
        Route::get('/get_divisions_and_subjects/{classId}', [AdminController::class, 'getDivisionsAndSubjects']); //Done   list the division and  the subject which are already allocated.
        Route::post('/store_subject_allotment', [AdminController::class, 'storeSubjectAllotment']); //Done 

        // Allot Teacher for a class 
        Route::get('/subject-allotment/section/{section_id}', [AdminController::class, 'getSubjectAllotmentWithTeachersBySection']);//Done   list the subject and the teachers
        // Route::put('/teacher-allotment/update', [AdminController::class, 'updateTeacherAllotment']);
        Route::put('/subject-allotments/{classId}/{sectionId}', [AdminController::class, 'updateTeacherAllotment']);
       
        // Allot Teachers 
        Route::get('/get_divisions/{classId}', [AdminController::class, 'getDivisionsbyClass']); //Done  Allot teacher tab list the division for the selected class.
        Route::get('/get_subjects/{sectionId}', [AdminController::class, 'getSubjectsbyDivision']);  //Done   Allot teacher tab list the subject  for the selected Division. 
        Route::get('/get_presubjects/{classId}', [AdminController::class, 'getPresignSubjectByDivision']);  //Done   Allot teacher tab list the subject(Presign Subjects )  for the selected Division. 
        Route::get('/get_presubjectss/{sectionId}', [AdminController::class, 'getSubjectsByDivisionWithAssigned']);  //Done   Allot teacher tab list the subject(Presign Subjects )  for the selected Division. 
        Route::get('/get_teacher_list', [AdminController::class, 'getTeacherNames']); //Done  Get the teacher list 
        Route::get('/get_presign_subject_by_teacher/{classID}/{sectionId}/{teacherID}', [AdminController::class, 'getPresignSubjectByTeacher']); // get the list of the preasign subject base on the selected clss_id,section_id,teacher_id .
        Route::post('/allot-teacher-for-subject/{class_id}/{section_id}', [AdminController::class, 'updateOrCreateSubjectAllotments']);



        // Route::post('/allotTeacherForSubjects', [AdminController::class, 'allotTeacherForSubjects']);
        // Route::get('/class/{classId}/subjects-allotment', [AdminController::class, 'getSubjectsAndSectionsByClass']);
        // Route::post('/allocate-teacher-for-class', [AdminController::class, 'allocateTeacherForClass']);
        // Route::get('/subject-allotment/{subjectId}/edit', [AdminController::class, 'editallocateTeacherForClass']);
        // Route::put('/subject-allotment/{subjectId}', [AdminController::class, 'updateallocateTeacherForClass']);
        // Route::delete('/subject-allotment/{subjectId}', [AdminController::class, 'deleteSubjectAlloted']);


        // Route::get('/student_base_on_class_id', [AdminController::class, 'getStudentListBaseonClass']);

        // Student Model Routes.
        Route::get('/getallClassWithStudentCount', [AdminController::class, 'getallSectionsWithStudentCount']);// Done for class dropdown.
        Route::get('/getStudentListBySection', [AdminController::class, 'getStudentListBySection']);// Done for student dropdown.
        Route::get('/getStudentListBySectionData',[AdminController::class,'getStudentListBySectionData']);
        Route::get('/students/{studentId}', [AdminController::class, 'getStudentById']); // Edit Student , for the view Student. and single student select for the list.
        Route::get('/student_by_reg_no/{reg_no}', [AdminController::class, 'getStudentByGRN']); // Student By GRN .
        Route::delete('/students/{studentId}', [AdminController::class, 'deleteStudent']);
        Route::patch('/students/{studentId}/deactivate', [AdminController::class, 'toggleActiveStudent']); // Done.
        Route::put('/students/{studentId}', [AdminController::class, 'updateStudentAndParent']);  
        Route::get('/check-user-id/{studentId}/{userId}', [AdminController::class, 'checkUserId']);  // API for the User_id unique check 
        Route::put('/resetPasssword/{user_id}', [AdminController::class, 'resetPasssword']);        

        //routes for the SubjectForReportCard
        Route::post('/check_subject_name_for_report_card', [AdminController::class, 'checkSubjectNameForReportCard']);
        Route::get('/subject_for_reportcard', [AdminController::class, 'getSubjectsForReportCard']);
        Route::post('/subject_for_reportcard', [AdminController::class, 'storeSubjectForReportCard']);
        Route::get('/subject_for_reportcard/{sub_rc_master_id}', [AdminController::class, 'editSubjectForReportCard']);
        Route::put('/subject_for_reportcard/{sub_rc_master_id}', [AdminController::class, 'updateSubjectForReportCard']);
        Route::delete('/subject_for_reportcard/{sub_rc_master_id}', [AdminController::class, 'deleteSubjectForReportCard']);
        
        //routes for the SubjectAllotment for the Report Card 
        Route::get('/get_subject_Alloted_for_report_card/{class_id}', [AdminController::class, 'getSubjectAllotmentForReportCard']);
        Route::get('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'getSubjectAllotmentById']);
        Route::put('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'updateSubjectType']);
        Route::delete('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'deleteSubjectAllotmentforReportcard']);
        Route::get('/get_sub_report_allotted/{class_id}/{subject_type}', [AdminController::class, 'editSubjectAllotmentforReportCard']);
        // Route::put('/get_sub_report_allotted/{class_id}', [AdminController::class, 'createOrUpdateSubjectAllotment']);
        Route::post('/subject-allotments-reportcard/{class_id}', [AdminController::class, 'createOrUpdateSubjectAllotment']);

        //Caretaker Module API
        Route::get('/get_caretaker',[NewController::class,'getCaretakerList']);
        Route::post('/save_caretaker',[NewController::class,'storeCaretaker']);
        Route::get('/edit_caretaker/{id}',[NewController::class,'editCaretaker']);
        Route::put('/update_caretaker/{id}',[NewController::class,'updateCaretaker']);
        Route::delete('/delete_caretaker/{id}', [NewController::class, 'deleteCaretaker']);
        Route::get('/get_teachercategory',[NewController::class,'getTeacherCategory']);

        //Bonafide Certificate
        Route::get('/get_srnobonafide/{id}',[CertificateController::class,'getSrnobonafide']);
        Route::post('/save_pdfbonafide', [CertificateController::class, 'downloadPdf']);
        Route::get('/get_bonafidecertificatelist',[CertificateController::class,'bonafideCertificateList']);
        Route::put('/update_isIssued/{sr_no}',[CertificateController::class,'updateisIssued']);
        Route::delete('/delete_isDeleted/{sr_no}',[CertificateController::class,'updateisDeleted']);
        Route::get('/get_bonafidecertificatedownload/{sr_no}',[CertificateController::class,'getPDFdownloadBonafide']);
        Route::get('get_databonafidestudent/{sr_no}',[CertificateController::class,'DataStudentBonafide']);
        Route::put('update_bonafidecertificate/{sr_no}',[CertificateController::class,'updateBonafideCertificate']);

        //Simple Bonafide Certificate
        Route::get('/get_srnosimplebonafide/{id}',[CertificateController::class,'getSrnosimplebonafide']);
        Route::post('/save_pdfsimplebonafide', [CertificateController::class, 'downloadsimplePdf']);
        Route::get('/get_simplebonafidecertificatelist',[CertificateController::class,'simplebonafideCertificateList']);
        Route::put('/update_simpleisIssued/{sr_no}',[CertificateController::class,'updatesimpleisIssued']);
        Route::delete('/delete_simpleisDeleted/{sr_no}',[CertificateController::class,'deletesimpleisDeleted']);
        Route::get('/get_simpleisDownload/{sr_no}',[CertificateController::class,'simpleBonafideDownload']);
        Route::get('get_datasimplebonafidestudent/{sr_no}',[CertificateController::class,'DataStudentSimpleBonafide']);
        Route::put('/update_simplebonafidecertificate/{sr_no}',[CertificateController::class,'updateSimpleBonafide']);

        //Bonafide Caste Certificate
        Route::get('/get_srnocastebonafide/{id}',[CertificateController::class,'getSrnocastebonafide']);
        Route::post('/save_pdfcastebonafide',[CertificateController::class,'downloadcastePDF']);
        Route::get('/get_castebonafidecertificatelist',[CertificateController::class,'castebonafideCertificateList']);
        Route::put('/update_casteisIssued/{sr_no}',[CertificateController::class,'updatecasteisIssued']);
        Route::delete('/delete_casteisDeleted/{sr_no}',[CertificateController::class,'deletecasteisDeleted']);
        Route::get('/get_casteisDownload/{sr_no}',[CertificateController::class,'CasteBonafideDownload']);
        Route::get('get_datacastecertificate/{sr_no}',[CertificateController::class,'DataCasteBonafide']);
        Route::put('update_castebonafidecertificate/{sr_no}',[CertificateController::class,'updateCasteBonafide']);

        //Bonafide Character Certificate
        Route::get('/get_srnocharacterbonafide/{id}',[CertificateController::class,'getSrnocharacterbonafide']);
        Route::post('/save_pdfcharacterbonafide',[CertificateController::class,'downloadcharacterPDF']);
        Route::get('/get_characterbonafidecertificatelist',[CertificateController::class,'characterbonafideCertificateList']);
        Route::put('/update_characterisIssued/{sr_no}',[CertificateController::class,'updatecharacterisIssued']);
        Route::delete('/delete_characterisDeleted/{sr_no}',[CertificateController::class,'deletecharacterisDeleted']);
        Route::get('get_characterisDownload/{sr_no}',[CertificateController::class,'CharacterBonafideDownload']);
        Route::get('get_characterdata/{sr_no}',[CertificateController::class,'DataCharacterBonafide']);
        Route::put('update_charactercertificate/{sr_no}',[CertificateController::class,'updateCharacterBonafide']);
        
        //Bonafide Percentage Certificate
        Route::get('get_srnopercentagebonafide/{id}',[CertificateController::class,'getSrnopercentagebonafide']);
        Route::post('save_pdfpercentagebonafide',[CertificateController::class,'downloadpercentagePDF']);
        Route::get('/get_percentagebonafidecertificatelist',[CertificateController::class,'percentagebonafideCertificateList']);
        Route::put('/update_percentageisIssued/{sr_no}',[CertificateController::class,'updatepercentageisIssued']);
        Route::delete('/delete_percentageisDeleted/{sr_no}',[CertificateController::class,'deletepercentageisDeleted']);
        Route::get('get_percentageisDownload/{sr_no}',[CertificateController::class,'PercentageDownload']); 
        Route::get('get_percentageData/{sr_no}',[CertificateController::class,'getPercentageData']);
        Route::put('update_percentagePDF/{sr_no}',[CertificateController::class,'updatePercentagePDF']);

        //Generate Leaving Certificate
        Route::get('get_srnoleavingcertificatedata/{id}',[CertificateController::class,'getSrnoLeavingCertificate']);
        Route::get('get_srnoleavingcertificateByAcademicyr/{id}/{academic_yr}',[CertificateController::class,'getSrnoLeavingCertificateAcademicYr']);
        Route::post('save_pdfleavingcertificate',[CertificateController::class,'saveLeavingCertificatePDF']);

        //Manage Leaving Certificate
        Route::get('get_leavingcertificatelist',[CertificateController::class,'getLeavingCertificateList']);
        Route::put('update_leavingcertificateisIssued/{sr_no}',[CertificateController::class,'leavingCertificateisIssued']);
        Route::delete('delete_leavingcertificateisDeleted/{sr_no}',[CertificateController::class,'leavingCertificateisDeleted']);
        Route::get('get_pdfleavingcertificate/{sr_no}',[CertificateController::class,'leavingCertificatePDFDownload']);
        Route::get('get_getleavingcertificatedata/{sr_no}',[CertificateController::class,'getLeavingCertificateDataSingle']);
        Route::put('update_leavingcertificate/{sr_no}',[CertificateController::class,'updateLeavingCertificateDownload']);

        //LC Student List
        Route::get('get_leavingcertificatestudentlist',[CertificateController::class,'getLeavingCertificateStudent']);
        Route::get('get_leavingcertificatedetailstudent/{student_id}',[CertificateController::class,'getLeavingCertificateDetailStudent']);
        Route::get('get_leavingcertificatestudentinformation/{student_id}',[CertificateController::class,'getStudentInformationleaving']);
        Route::delete('delete_deletestudentleaving/{student_id}',[CertificateController::class,'deleteStudentLeaving']);

        //Deleted Student
        Route::get('get_deletedstudentlist',[CertificateController::class,'getDeletedStudentList']);
        Route::put('update_adddeletedstudent/{student_id}',[CertificateController::class,'addDeletedStudent']);

        //Notice/Sms
        Route::post('save_smsnotice',[NoticeController::class,'saveSmsNotice']);
        Route::post('save_publish_smsnotice',[NoticeController::class,'SaveAndPublishSms']);
        Route::get('get_smsnoticelist',[NoticeController::class,'getNoticeSmsList']);
        Route::get('get_smsnoticedata/{unq_id}',[NoticeController::class,'getNoticeSmsData']);
        Route::post('update_smsnotice/{unq_id}',[NoticeController::class,'UpdateSMSNotice']);
        Route::delete('delete_smsnotice/{unq_id}',[NoticeController::class,'DeleteSMSNotice']);
        Route::put('update_publishsmsnotice/{unq_id}',[NoticeController::class,'publishSMSNotice']);
        Route::post('save_noticesmspdf',[NoticeController::class,'saveNotice']);
        Route::post('save_publishnoticesmspdf',[NoticeController::class,'savePUblishNotice']);
        Route::post('save_sendsms/{unq_id}',[NoticeController::class,'SendSMSLeft']);

        //Exam TimeTable
        Route::get('get_examdates/{class_id}/{exam_id}',[NoticeController::class,'getExamDateswithnames']);
        Route::post('save_timetable/{exam_id}/{class_id}',[NoticeController::class,'saveExamTimetable']);
        Route::get('get_subjectsofallstudents/{class_id}',[NoticeController::class,'getAllSubjects']);
        Route::get('get_timetablelist',[NoticeController::class,'getTimetableList']);
        Route::delete('delete_timetable/{exam_tt_id}',[NoticeController::class,'deleteTimetable']);
        Route::put('update_publishtimetable/{exam_tt_id}',[NoticeController::class,'updatePublishTimetable']);
        Route::put('update_unpublishtimetable/{exam_tt_id}',[NoticeController::class,'updateunPublishTimetable']);
        Route::get('get_viewtimetable',[NoticeController::class,'viewTimetableStudent']);
        Route::get('get_examtimetable/{exam_tt_id}',[NoticeController::class,'getExamdataSingle']);
        Route::put('update_examtimetable/{exam_tt_id}',[NoticeController::class,'updateExamTimetable']);

        //Substitute Teacher
        Route::get('get_teachersubstitutionlist',[SubstituteTeacher::class,'getTeacherListforSubstitution']);
        Route::get('get_substituteteacher/{teacher_id}/{date}',[SubstituteTeacher::class,'getSubstituteTeacherDetails']);
        Route::get('get_substituteteacherclasswise/{class_name}/{period}/{date}',[SubstituteTeacher::class,'getSubstituteTeacherClasswise']);
        Route::post('save_substituteteacher',[SubstituteTeacher::class,'saveSubstituteTeacher']);
        Route::get('get_substituteteacherdata/{teacher_id}/{date}',[SubstituteTeacher::class,'getSubstituteTeacherData']);
        Route::put('update_substituteteacher/{teacher_id}/{date}',[SubstituteTeacher::class,'updateSubstituteTeacher']);
        Route::delete('delete_subsituteteacher/{teacher_id}/{date}',[SubstituteTeacher::class,'deleteSubstituteTeacher']);

        

        Route::get('download_csv_rejected/{id}',[AdminController::class,'downloadCsvRejected']);

        //Set Late Time
        Route::post('save_setlatetime',[SubstituteTeacher::class,'saveLateTime']);
        Route::get('get_listlatetime',[SubstituteTeacher::class,'LateTimeList']);
        Route::get('get_latetimedata/{lt_id}',[SubstituteTeacher::class,'LateTimeData']);
        Route::put('update_latetime/{lt_id}',[SubstituteTeacher::class,'updateLateTime']);
        Route::delete('delete_latetime/{lt_id}',[SubstituteTeacher::class,'deleteLateTime']);

        //Promote Students
        Route::get('getstudentlistbyclassdivision/{class_id}/{section_id}',[StudentController::class,'getStudentListClass']);
        Route::get('nextclassacademicyear',[StudentController::class,'nextClassPromote']);
        Route::get('nextsectionacademicyear/{class_id}',[StudentController::class,'nextSectionPromote']);
        Route::post('promotestudents',[StudentController::class,'promoteStudentsUpdate']);

        //Leave Allocation
        Route::get('get_leavetype',[AdminController::class,'getLeavetype']);
        Route::get('get_allstaff',[AdminController::class,'getAllStaff']);
        Route::post('save_leaveallocated',[AdminController::class,'saveLeaveAllocated']);
        Route::get('get_leaveallocationall',[AdminController::class,'leaveAllocationall']);
        Route::get('get_leaveallocationdata/{staff_id}/{leave_type_id}',[AdminController::class,'getLeaveAllocationdata']);
        Route::put('update_leaveallocation/{staff_id}/{leave_type_id}',[AdminController::class,'updateLeaveAllocation']);
        Route::delete('delete_leaveallocation/{staff_id}/{leave_type_id}',[AdminController::class,'deleteLeaveAllocation']);

        //Manage Student
        Route::get('get_students',[AdminController::class,'getStudentsList']);

        //Leave Allocation for all staff
        Route::post('save_leaveallocationforallstaff',[AdminController::class,'saveLeaveAllocationforallStaff']);
        
        //Send user id to password
        Route::post('send_user_id_toparents',[AdminController::class,'sendUserIdParents']);

        //Leave Application
        Route::get('get_leavetypedata/{staff_id}',[AdminController::class,'getLeavetypedata']);
        Route::post('save_leaveapplication',[AdminController::class,'saveLeaveApplication']);
        Route::get('get_leaveapplicationlist',[AdminController::class,'getLeaveApplicationList']);
        Route::get('get_leaveapplieddata/{leave_app_id}',[AdminController::class,'getLeaveAppliedData']);
        Route::put('update_leaveapplication/{leave_app_id}',[AdminController::class,'updateLeaveApplication']);
        Route::delete('delete_leaveapplication/{leave_app_id}',[AdminController::class,'deleteLeaveApplication']);

        //Sibling Mapping
        Route::post('save_siblingmapping',[AdminController::class,'saveSiblingMapping']);

        //Studentwise Subject Allotment for hsc
        Route::get('get_subject_group',[HscController::class,'getSubjectGroup']);
        Route::get('get_optional_subject',[HscController::class,'getOptionalSubject']);
        Route::get('get_subjecthigherstudentwise/{class_id}/{section_id}',[HscController::class,'getSubjectStudentwise']);
        Route::post('save_subjectforhsc',[HscController::class,'saveSubjectforHsc']);

        //Leave type
        Route::post('save_leavetype',[AdminController::class,'saveLeavetype']);
        Route::get('get_allleavetype',[AdminController::class,'getallleavetype']);
        Route::get('get_leavetypesingle/{id}',[AdminController::class,'getLeaveData']);
        Route::put('update_leavetype/{id}',[AdminController::class,'updateLeavetype']);
        Route::delete('delete_leavetype/{id}',[AdminController::class,'deleteLeavetype']);

        //Allot GR No.
        Route::get('get_studentallotgrno/{id}',[AdminController::class,'studentAllotGrno']);
        Route::put('update_studentallotgrno',[AdminController::class,'updateStudentAllotGrno']);

        //Update Category and Religion
        Route::get('get_studentcategoryreligion/{class_id}/{section_id}',[AdminController::class,'getStudentCategoryReligion']);
        Route::put('update_studentcategoryreligion',[AdminController::class,'updateStudentCategoryReligion']);

        //Update Student Id and other details
        Route::get('get_studentidotherdetails/{class_id}/{section_id}',[AdminController::class,'getStudentOtherDetails']);
        Route::put('update_studentidotherdetails',[AdminController::class,'updateStudentIdOtherDetails']);

        //Student Id Card Dev Name - Manish Kumar Sharma 25-02-2025
        Route::get('get_studentidcard',[AdminController::class,'getStudentIdCard']);
        Route::get('get_ziparchive',[AdminController::class,'getziparchivestudentimages']);
        Route::get('get_studentdatawithparentdata',[AdminController::class,'getStudentDataWithParentData']);
        Route::post('save_studentparentguardianimage',[AdminController::class,'saveStudentParentGuardianImage']);
        // Route::get('get_excelstudentidcard',[AdminController::class,'getStudentexcelIdCard']);

        //Holiday List Dev Name - Manish Kumar Sharma 18-02-2025
        Route::post('save_holiday',[AdminController::class,'saveHoliday']);
        Route::post('save_holidaypublish',[AdminController::class,'saveHolidaypublish']);
        Route::get('get_holidaylist',[AdminController::class,'getholidayList']);
        Route::delete('delete_holiday/{holiday_id}',[AdminController::class,'deleteHoliday']);
        Route::put('update_publishholiday',[AdminController::class,'updatepublishholiday']);
        Route::put('update_holiday/{holiday_id}',[AdminController::class,'updateHoliday']);
        Route::get('get_templatecsv',[AdminController::class,'downloadCsvTemplate']);
        Route::post('update_holidaylist_csv',[AdminController::class,'updateholidaylistCsv']);
        


        //Timetable Dev Name - Manish Kumar Sharma 18-02-2025
        Route::get('get_fieldsfortimetable',[AdminController::class,'fieldsForTimetable']);
        Route::get('get_subjectfortimetable',[AdminController::class,'getSubjectTimetable']);
        Route::delete('delete_timetable/{class_id}/{section_id}',[AdminController::class,'deleteTimetable']);
        Route::get('get_timetableforclass/{class_id}/{section_id}',[AdminController::class,'getTimetableForClass']);


        //Teacher Id Card Dev Name - Manish Kumar Sharma 26-02-2025
        Route::get('get_teacheridcard',[AdminController::class,'getTeacherIdCard']);
        Route::get('get_teacherziparchiveimages',[AdminController::class,'getTeacherzipimages']);
        
        //Stationery Dev Name- Manish Kumar Sharma 26-02-2025
        Route::post('save_stationery',[AdminController::class,'saveStationery']);
        Route::get('get_stationery',[AdminController::class,'getStationeryList']);
        Route::put('update_stationery/{stationery_id}',[AdminController::class,'updateStationery']);
        Route::delete('delete_stationery/{stationery_id}',[AdminController::class,'deleteStationery']);


        //Timetable Dev Name - Manish Kumar Sharma 27-02-2025
        Route::post('save_classtimetable',[AdminController::class,'saveClassTimetable']);
        Route::get('get_classtimetable/{class_id}/{section_id}',[AdminController::class,'viewclassTimetable']);
        Route::put('update_classtimetable',[AdminController::class,'updateClasstimetable']);

        //Pending Student Id Card Dev Name - Manish Kumar Sharma 28-02-2025
        Route::get('get_pendingstudentidcard',[AdminController::class,'getPendingStudentIdCard']);
        Route::put('update_pendingstudentidcard',[AdminController::class,'updatePendingStudentIdCard']);

        //Reports Dev Name - Manish Kumar Sharma 01-03-2025
        Route::get('get_classofnewadmission',[ReportController::class,'getClassofNewStudent']);
        Route::get('get_reportofnewadmission',[ReportController::class,'getReportofNewAdmission']);

        //Reports Balance Leave Dev Name - Manish Kumar Sharma 03-03-2025
        Route::get('get_balanceleavereport',[ReportController::class,'getBalanceLeaveReport']);

        //Reports Consolidated Leave Dev Name - Manish Kumar Sharma 03-03-2025
        Route::get('get_consolidatedleavereport',[ReportController::class,'getConsolidatedLeaveReport']);
        //Reports Student Report Dev Name - Manish Kumar Sharma 03-03-2025
        Route::get('get_studentreport',[ReportController::class,'getStudentReport']);

        //Reports Student Contact Details report Dev name - Manish Kumar Sharma 10-03-2025
        Route::get('get_studentcontactdetailsreport',[ReportController::class,'getContactDetailsReport']);

        //Reports Student Remarks Report Dev Name- Manish Kumar Sharma 10-03-2025
        Route::get('get_studentremarksreport',[ReportController::class,'getStudentRemarksReport']);

        //Reports Categorywise Student Report Dev Name- Manish Kumar Sharma 10-03-2025
        Route::get('get_categorywisestudentreport',[ReportController::class,'getCategoryWiseStudentReport']);

        //Reports Religionwise Student Report Dev Name- Manish Kumar Sharma 10-03-2025
        Route::get('get_religionwisestudentreport',[ReportController::class,'getReligionWiseStudentReport']);

        //Reports Genderwise Student Report Dev Name- Manish Kumar Sharma 10-03-2025
        Route::get('get_genderwisestudentreport',[ReportController::class,'getGenderWiseStudentReport']);

        //Reports Genderwise Religionwise Report Dev Name- Manish Kumar Sharma 10-03-2025
        Route::get('get_religiongenderwisestudentreport',[ReportController::class,'getGenderReligionwiseReport']);

        //Reports Genderwise Categorywise Report Dev Name- Manish Kumar Sharma 10-03-2025
        Route::get('get_gendercategorywisestudentreport',[ReportController::class,'getGenderCategorywiseReport']);
        
        //Reports New Student Report Dev Name-Manish Kumar Sharma 17-03-2025
        Route::get('get_newstudentreport',[ReportController::class,'getNewStudentReport']);

        //Reports Left Students Report Dev Name-Manish Kumar Sharma 18-03-2025
        Route::get('get_leftstudentreport',[ReportController::class,'getLeftStudentReport']);

        //Reports Subject HSC Studentwise Report Dev Name-Manish Kumar Sharma 18-03-2025
        Route::get('get_subjectshscstudentwisereport',[ReportController::class,'getSubjectHSCStudentwiseReport']);

        //Reports Staff Report Dev Name-Manish Kumar Sharma 19-03-2025
        Route::get('get_staff_report',[ReportController::class,'getStaffReport']);

        //Reports Monthly Attendance Report Dev Name-Manish Kumar Sharma 19-03-2025
        Route::get('get_monthly_attendance_report',[ReportController::class,'getMonthlyAttendanceReport']);
        
        //Reports Fee Payment Record Report Dev Name-Manish Kumar Sharma 20-03-2025
        Route::get('getfeepaymentrecordreport',[ReportController::class,'getFeePaymentRecordReport']);

        //Reports WorldLine Fee Payment Record Report Dev Name-Manish Kumar Sharma 20-03-2025
        Route::get('getworldfeepaymentrecordreport',[ReportController::class,'getWorldlineFeePaymentRecordReport']);

        //Reports Razorpay Fee Payment Record Report Dev Name-Manish Kumar Sharma 20-03-2025
        Route::get('getrazorpayfeepaymentreport',[ReportController::class,'getRazorpayFeePaymentRecordReport']);

        //Reports Pending Student Id Card Record Report Dev Name-Manish Kumar Sharma 24-03-2025
        Route::get('getpendingstudentidcardreport',[ReportController::class,'getPendingStudentIdCardRecordReport']);

        //Reports Substitute Teacher Monthly Report Dev Name-Manish Kumar Sharma 24-03-2025
        Route::get('getsubstituteteachermonthlyreport',[ReportController::class,'getSubstituteTeacherMonthlyReport']);

        //Reports Substitute Teacher Weekly Report Dev Name-Manish Kumar Sharma 24-03-2025
        Route::get('getsubstituteteacherweeklyreport',[ReportController::class,'getSubstituteTeacherWeeklyReport']);

        //Reports Leaving Certificate Report Dev Name-Manish Kumar Sharma 24-03-2025
        Route::get('getleavingcertificatereport',[ReportController::class,'getLeavingCertificateReport']);

        //Manage Student Report Cards & Certificates Dev Name-Manish Kumar Sharma 25-03-2025
        Route::get('getstudentremarkobservation',[AdminController::class,'getStudentRemarkObservation']);
        //Manage Student Report Cards & Certificates Dev Name-Manish Kumar Sharma 26-03-2025
        Route::get('getstudentdatabystudentid',[AdminController::class,'getStudentDataByStudentId']);
        Route::get('getacademicyrbysettings',[AdminController::class,'getAcademicYrBySettings']);
        Route::get('health_activity_data_pdf',[AdminController::class,'getHealthActivityPdf']);

        //Teachers Period Allocation Dev Name- Manish Kumar Sharma 29-03-2025
        Route::get('get_departments',[AdminController::class,'getDepartmentss']);
        Route::get('get_teacherperiodallocation',[AdminController::class,'getTeacherPeriodAllocation']);
        Route::post('save_teacherperiodallocation',[AdminController::class,'saveTeacherPeriodAllocation']);
        Route::get('get_subjectwithoutsocial',[AdminController::class,'getSubjectWithoutSocial']);
        Route::get('get_teacherclasstimetable',[AdminController::class,'getTeacherClassTimetable']);

        //Classwise Period Allocation Dev Name- Manish Kumar Sharma 31-03-2025
        Route::get('get_classsectionfortimetable',[AdminController::class,'getClassSection']);
        Route::post('save_classwiseperiod',[AdminController::class,'saveClasswisePeriod']);
        Route::get('get_classwiseperiodlist',[AdminController::class,'getClasswisePeriodList']);
        Route::put('update_classwiseperiod/{class_id}/{section_id}',[AdminController::class,'updateClasswisePeriod']);
        Route::delete('delete_classwiseperiod/{class_id}/{section_id}',[AdminController::class,'deleteClasswisePeriod']);
        

        //Timetable Teacherwise  Dev Name- Manish Kumar Sharma 01-04-2025
        Route::get('get_teacherperioddata',[AdminController::class,'getTeacherPeriodData']);
        Route::get('get_teachersubjectbyclass',[AdminController::class,'getTeacherSubjectByClass']);
        Route::get('get_teacherslistbyperiod',[AdminController::class,'getTeacherListByPeriod']);
        Route::get('get_timetablebyclasssection/{class_id}/{section_id}/{teacher_id}',[AdminController::class,'getTimetableByClassSection']);
        Route::post('save_timetableallotment',[AdminController::class,'saveTimetableAllotment']);

        //Timetable Edit Teacherwise Dev Name- Manish Kumar Sharma 07-04-2025
        Route::get('get_teacherlistbyperiodallocation',[AdminController::class,'getTeacherlistByperiodallocation']);
        Route::get('get_edittimetablebyclasssection/{class_id}/{section_id}/{teacher_id}',[AdminController::class,'getEditTimetableClassSection']);

        //API for All Student List with Class Name Dev Name- Manish Kumar Sharma 09-04-2025
        Route::get('get_allstudentwithclass',[StudentController::class,'getallStudentWithClass']);

        //Delete Teacher Periods Dev Name-Manish Kumar Sharma 14-04-2025
        Route::delete('delete_teacherperiodintimetable/{teacher_id}',[AdminController::class,'deleteTeacherPeriodTimetable']);

        //Get SectionId with ClassName Dev Name-Manish Kumar Sharma 21-04-2025
        Route::get('get_sectionwithclassname',[AdminController::class,'getSectionwithClassName']);

        //Classes for new StudentList Dev Name- Manish Kumar Sharma 29-04-2025
        Route::get('get_classesfornewstudentlist',[AdminController::class,'getClassesforNewStudentList']);

        //Birthday list for student and staff Dev Name- Manish Kumar Sharma 30-04-2025
        Route::get('get_birthdaylistforstaffstudent',[AdminController::class,'getBirthdayListForStaffStudent']);

        //Student Id Card New Implementation Dev Name- Manish Kumar Sharma 30-04-2025
        Route::get('get_studentidcarddetails',[AdminController::class,'getStudentIdCardDetails']);

        //Student Id Card New Implementation Dev Name- Manish Kumar Sharma 30-04-2025
        Route::get('get_studentidcarddetails',[AdminController::class,'getStudentIdCardDetails']);
        Route::post('save_studentdetailsforidcard',[AdminController::class,'saveStudentDetailsForIdCard']);

        //Update Id Card Data New Implementation Dev Name- Manish Kumar Sharma 30-04-2025
        Route::get('get_update_idcard_data_by_teacher',[AdminController::class,'getUpdateIdCardData']);
        Route::put('update_idcarddata',[AdminController::class,'updateIdCardData']);
        Route::put('update_idcarddataandconfirm',[AdminController::class,'updateIdCardDataAndConfirm']);
        Route::put('update_studentphotoforidcard',[AdminController::class,'updateStudentPhotoForIdCard']);

        //Update Id Card Data New Implementation Dev Name- Manish Kumar Sharma 05-05-2025
        Route::get('get_parentandguardianimage',[AdminController::class,'getParentAndGuardianImage']);
        Route::post('update_parentguradianimage',[AdminController::class,'updateParentGuardianImage']);
        //API for the View Staff Notices Dev Name- Manish Kumar Sharma 06-05-2025
        Route::get('get_viewstaffnotices',[NoticeController::class,'getViewStaffNotices']);

        //API for the Roles  Dev Name- Manish Kumar Sharma 12-05-2025
        Route::put('update_activeinactiverole/{id}',[RoleController::class,'update_activeinactiverole']);
        //API for the Roles and Menus  Dev Name- Manish Kumar Sharma 12-05-2025
        Route::delete('delete_rolesandmenus/{id}',[RoleController::class,'deleterolesandmenus']);

        //API for the Absent Student  Dev Name- Manish Kumar Sharma 19-05-2025
        Route::get('get_absentstudentfortoday',[AdminController::class,'getAbsentStudentForToday']);

        //API for the Absent Teacher  Dev Name- Manish Kumar Sharma 19-05-2025
        Route::get('get_absentteacherfortoday',[AdminController::class,'getAbsentTeacherForToday']);

        //API for the Absent Non Teacher  Dev Name- Manish Kumar Sharma 21-05-2025
        Route::get('get_absentnonteacherfortoday',[AdminController::class,'getAbsentnonTeacherForToday']);

        //API for the Lesson Plan Teacher  Dev Name- Manish Kumar Sharma 23-05-2025
        Route::get('get_lesson_plan_created_teachers',[AdminController::class,'get_lesson_plan_created_teachers']);

        //API for the Count Non Approved Lesson  Dev Name- Manish Kumar Sharma 23-05-2025
        Route::get('get_count_non_approved_lesson_plan',[AdminController::class,'getCountNonApprovedLessonPlan']);

        //API for the Maximum Sequence For Parent  Dev Name- Manish Kumar Sharma 26-05-2025
        Route::get('get_maximumsequenceforparent',[RoleController::class,'getMaximumSequenceForParent']);

        //API for the Notice for Staff  Dev Name- Manish Kumar Sharma 04-06-2025
        Route::get('get_departmentlist',[NoticeController::class,'getdepartmentlist']);
        Route::get('get_teacherlistbydepartment',[NoticeController::class,'getTeacherlistByDepartment']);
        Route::post('save_noticeforstaffsms',[NoticeController::class,'savenoticeforStaffSms']);
        Route::get('get_staffnoticelist',[NoticeController::class,'getStaffnoticeList']);

        //API for the Notice for Staff  Dev Name- Manish Kumar Sharma 05-06-2025
        Route::post('save_staffsavenpublishshortsms',[NoticeController::class,'savenPublishstaffshortsms']);
        Route::post('save_staffsavenotice',[NoticeController::class,'savestaffSaveNotice']);
        //API for the Notice for Staff  Dev Name- Manish Kumar Sharma 06-06-2025
        Route::delete('delete_staffshortsmsnotice/{unq_id}',[NoticeController::class,'deleteStaffShortSMSNotice']);
        Route::post('save_staffsavenpublishnotice',[NoticeController::class,'savestaffsavenPublishNotice']);
        Route::put('update_staffnoticesmspublish/{unq}',[NoticeController::class,'updatestaffNoticeSMSPublish']);
        Route::get('get_staffnoticedata/{unq_id}',[NoticeController::class,'getStaffNoticeData']);
        Route::post('update_staffsmsnotice/{unq_id}',[NoticeController::class,'updatestaffSMSNotice']);

        //API for the Leave Application for all staff Dev Name- Manish Kumar Sharma 06-06-2025
        Route::post('save_leaveapplicatstaffprincipal',[NewController::class,'saveLeaveApplicationForallstaff']);
        Route::get('get_leaveapplicationdata',[NewController::class,'getLeaveApplicationData']);
        Route::delete('delete_leaveapplicationprincipal/{id}',[NewController::class,'deleteLeaveApplicationPrincipal']);
        Route::put('update_leaveapplicationcancel/{id}',[NewController::class,'updateLeaveApplicationCancel']);
        Route::put('update_leaveapplicationdata/{id}',[NewController::class,'updateLeaveApplicationData']);

        //API for the Phase 1 Reports Dev Name- Manish Kumar Sharma 07-06-2025
        Route::get('get_discrepancy_in_WL_payment_report',[ReportController::class,'getdiscrepancy_in_WL_payment_report']);
        Route::get('get_duplicatepaymentreportFinance',[ReportController::class,'getduplicatepaymentreportfinance']);

        //API for the Remark and observation for teachers Dev Name- Manish Kumar Sharma 09-06-2025
        Route::post('save_remarkforteacher',[NewController::class,'saveRemarkForTeacher']);
        Route::post('save_savenpublishremarkforteacher',[NewController::class,'savenPublishRemarkForTeacher']);
        Route::get('get_remarkforteacherlist',[NewController::class,'getRemarkForTeacherList']);
        Route::put('update_updateremarkforteacher/{t_remark_id}',[NewController::class,'updateRemarkForTeacher']);
        Route::delete('delete_remarkforteacher/{t_remark_id}',[NewController::class,'deleteRemarkForTeacher']);
        Route::put('update_publishremarkforteacher/{t_remark_id}',[NewController::class,'updatePublishRemarkForTeacher']);

        //API for the Staff daily attendance report Dev Name- Manish Kumar Sharma 12-06-2025
        Route::get('get_staffdailyattendancereport',[ReportController::class,'getStaffDailyAttendanceReport']);

        //API for the Approve leave Dev Name- Manish Kumar Sharma 13-06-2025
        Route::get('get_listforleaveapprove',[ReportController::class,'getListForleaveApprove']);
        Route::post('update_leaveapprovestatus/{id}',[ReportController::class,'updateLeaveApproveStatus']);
        Route::get('get_count_of_approveleave',[ReportController::class,'getCountofApproveLeave']);

        //API for the Sending whatsapp messages to late teachers Dev Name- Manish Kumar Sharma 15-06-2025
        Route::post('send_whatsapplatecoming',[AdminController::class,'sendWhatsappLateComing']);

        //API for the Teacher attendance monthly report Dev Name- Manish Kumar Sharma 23-06-2025
        Route::get('get_teachermonthlyattendancereport/{month}',[ReportController::class, 'getTeacherAttendanceMonthlyReport']);

        //API for the service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
        Route::post('save_servicetypeticket',[NewController::class,'saveServiceTypeTicket']);
        Route::get('get_servicetypeticket',[NewController::class,'getServiceTypeTicket']);
        Route::delete('delete_servicetypeticket/{service_id}',[NewController::class,'deleteServiceTypeTicket']);
        Route::put('update_servicetypeticket/{service_id}',[NewController::class,'updateServiceTypeTicket']);

        //API for the sub service type ticket Dev Name- Manish Kumar Sharma 24-06-2025
        Route::post('save_subservicetypeticket',[NewController::class,'savesubServiceTypeTicket']);
        Route::get('get_subservicetypeticket',[NewController::class,'getsubServiceTypeTicket']);
        Route::delete('delete_subservicetypeticket/{sub_servicetype_id}',[NewController::class,'deletesubServiceTypeTicket']);
        Route::put('update_subservicetypeticket/{sub_servicetype_id}',[NewController::class,'updatesubServiceTypeTicket']);

        //API for the appointment window ticket Dev Name- Manish Kumar Sharma 24-06-2025
        Route::post('save_appointmentwindow',[NewController::class,'saveAppointmentWindow']);
        Route::get('get_appointmentwindowlist',[NewController::class,'getAppointmentWindowList']);
        Route::delete('delete_appointmentwindow/{aw_id}',[NewController::class,'deleteAppointmentWindow']);
        Route::put('update_appointmentwindow/{aw_id}',[NewController::class,'updateAppointmentWindow']);

        //API for the ticket report ticket Dev Name- Manish Kumar Sharma 24-06-2025
        Route::get('get_ticketreport',[NewController::class,'getTicketReport']);

        //API for the ticket list Dev Name- Manish Kumar Sharma 25-06-2025
        Route::get('get_ticketlist',[NewController::class,'getTicketList']);
        Route::get('get_ticketinformation/{ticket_id}',[NewController::class,'getTicketInformationByTicketId']);
        Route::get('get_statusesforticketlist',[NewController::class,'getStatusesForTicket']);
        Route::get('get_appointmenttimelist/{class_id}',[NewController::class,'getAppointmentTimeList']);
        Route::get('get_commentticketlist/{ticket_id}',[NewController::class,'getCommentTicketList']);
        Route::post('save_ticketinformation/{ticket_id}',[NewController::class,'saveTicketInformation']);

        //API for the timetable view classwise Dev Name- Manish Kumar Sharma 26-06-2025
        Route::get('get_timetableviewbyteacher/{class_id}/{section_id}/{teacher_id}',[AdminController::class,'Timetableviewbyteacherid']);
        Route::post('update_timetableforclass',[NewController::class,'updateTimetableAllotment']);

        // Api for the Sibling Unmapping Dev Name - Mahima Chaudhari 26-06-2025
        Route::get('get_studentwithSiblings', [SubstituteTeacher::class, 'getStudentsListwithSibling']);
        Route::post('update_studentwithsibling/{id}', [SubstituteTeacher::class, 'saveUnmappingSibling']);

        // Api for the Remark and observation for students Dev Name - Manish Kumar Sharma 03-07-2025
        Route::post('save_remarkobservationforstudents', [NewController::class,'saveRemarkObservationForStudents']);
        Route::get('get_remarklistforstudents', [NewController::class,'getRemarkObservationListForStudents']);
        Route::delete('delete_remarkforstudent/{remark_id}', [NewController::class,'deleteRemarkObservationForStudents']);
        Route::put('update_publishremarkforstudent/{remark_id}', [NewController::class,'updatepublishRemarkObservationForStudent']);
        Route::get('get_subject_alloted_to_teacher_by_class/{class_id}/{section_id}', [NewController::class,'getSubjectAllotedToTeacherByClass']);
        Route::get('get_subjectbyclasssection/{class_id}/{section_id}', [NewController::class,'getSubjectByClassSection']);
        Route::post('update_remarkforstudent/{remark_id}', [NewController::class,'updateRemarkObservationForStudent']);
        
        // Api for the allot special role for students Dev Name - Manish Kumar Sharma 04-07-2025
        Route::post('save_allotspecialrole', [NewController::class,'saveAllotSpecialRole']);
        Route::get('get_allotspecialrolelist',[NewController::class,'getSpecialrolelist']);
        Route::delete('delete_allotspecialrole/{special_role_id}',[NewController::class,'deleteSpecialrolelist']);
        Route::get('get_specialrole',[NewController::class,'getSpecialRole']);
        Route::put('update_allotspecialrole/{special_role_id}',[NewController::class,'updateallotspecialrole']);
        Route::get('get_allstaff_without_caretaker',[NewController::class,'getAllStaffwithoutCaretaker']);
        
        // Api for the substitute class teacher Dev Name - Manish Kumar Sharma 07-07-2025
        Route::post('save_classteachersubstitute',[NewController::class,'saveClassTeacherSubstitute']);
        Route::get('get_classteachers',[NewController::class,'getClassTeachers']);
        Route::get('get_nonclassteachers',[NewController::class,'getNonClassTeachers']);
        Route::get('get_substitute_classteacherlist',[NewController::class,'getsubstituteClassTeacherList']);
        Route::put('update_classteachersubstitute/{class_substitute_id}',[NewController::class,'updateClassTeacherSubstitute']);
        Route::delete('delete_substituteclassteacher/{class_substitute_id}',[NewController::class,'deleteSubstituteClassTeacher']);
        
        // Api for the finance fees category Dev Name - Manish Kumar Sharma 07-07-2025
        Route::get('get_feescategory',[NewController::class,'getFeesCategory']);

        // Api for the Fee category student allotment Dev Name - Manish Kumar Sharma 08-07-2025
        Route::get('get_feescategorystudentallotmentview',[NewController::class,'getFeesCategoryStudentAllotmentView']);
        
        //Api for the Fee Category Dev Name - Manish Kumar Sharma 08-07-2025
        Route::get('get_feescategoryallotmentview',[NewController::class,'getFeesCategoryAllotmentView']);
        Route::get('get_feescategoryallotmentinstallment',[NewController::class,'getFeesCategoryAllotmentInstallment']);
        Route::get('get_feescategoryinstallmentdropdown/{fees_category_id}/{selected?}',[NewController::class,'getFeesCategoryInstallmentDropdown']);
        
        //Api for the downloading of ticket comment file Dev Name - Manish Kumar Sharma 09-07-2025
        Route::get('downloadticketfiles/{ticket_id}/{comment_id}/{name}',[NewController::class,'downloadTicketFiles']);
        
        //Api for the downloading of ticket comment file Dev Name - Manish Kumar Sharma 09-07-2025
        Route::get('get_sendsmsforfeespendingdata/{class_id}/{installment}',[NewController::class,'getSendSMSForFeesPendingData']);
        Route::post('send_sendsmsforfeespending',[NewController::class,'SendSMSForFeesPending']);
        
        //Reports Staff Leave Report Dev Name-Mahima Suryakant Chaudhari 10-07-2025
        Route::get('getstaffleavereport', [ReportController::class, 'getStaffLeaveReport']);

        //Reports Lesson Plan Status Report Dev Name-Manish Kumar Sharma 14-07-2025
        Route::get('get_lesson_plan_status_report',[ReportController::class,'getLessonPlanStatusReport']);
        
        //Reports Lesson Plan Summarised Report Dev Name - Manish Kumar Sharma 14-07-2025
        Route::get('get_lesson_plan_summarised_report',[ReportController::class,'getLessonPlanSummarisedReport']);
        
        //Reports Lesson Plan detailed Report Dev Name - Manish Kumar Sharma 14-07-2025
        Route::get('get_lesson_plan_detailed_report',[ReportController::class,'getLessonPlanDetailedReport']);

        // Api for Teacher Remark Report Dev Name - Mahima Chaudhari 14-07-2025
        Route::get('getteacherremarkreport', [ReportController::class, 'teachersRemarkReport']);
        
        // Api for Teacher Remark Report Dev Name - Mahima Chaudhari 15-07-2025
        Route::get('getstaffyearwiseattendancereport', [ReportController::class, 'getStaffDetailedYearwiseAttendance']);
        
        // Api for View Daily Attendance monthwise Report Dev Name - Manish Kumar Sharma 15-07-2025
        Route::get('get_studentdailyattendancemonthwise', [ReportController::class, 'getStudentDailyAttendanceMonthwise']);
        
        // Api for View Daily Attendance monthwise Report Dev Name - Manish Kumar Sharma 15-07-2025
        Route::get('get_teacherallsubjects', [NewController::class, 'getTeacherAllSubjects']);
        
        //Api for Attendance marking status Report Dev Name - Manish Kumar Sharma 15-07-2025
        Route::get('get_attendancemarkingstatus', [ReportController::class, 'getAttendanceMarkingStatus']);
        
        //Api for homework status Report Dev Name - Manish Kumar Sharma 18-07-2025
        Route::get('get_homeworkstatusreport', [ReportController::class, 'getHomeworkStatusReport']);
        
        //Api for homework status Report Dev Name - Manish Kumar Sharma 18-07-2025
         Route::get('get_teachersbyclassidsectionid',[ReportController::class,'getTeachersByClassSection']);
         Route::get('get_homeworknotassignedreport',[ReportController::class,'getHomeworkNotAssignedReport']);
        
        
        // Api for Classwise Homework Details Report Dev Name - Mahima Chaudhari 19-07-2025
        Route::get('getclasswisehomeworkreport', [ReportController::class, 'getClasswiseHomework']);
        
        // Api for Classwise Report marks report Report Dev Name - Manish Kumar Sharma 21-07-2025
        Route::get('get_classwisereportcardmarksreport', [ReportController::class, 'getClasswiseReportCardMarksReport']);
        
        // Api for Classwise Report marks report Report Dev Name - Manish Kumar Sharma 21-07-2025
        Route::get('get_classwisemarksreport', [ReportController::class, 'getClasswiseMarksReport']);
        Route::get('get_classwisemarksreportchanges', [ReportController::class, 'getClasswiseMarksReportchanges']);
        
        // Api for Approve lesson plan Dev Name - Manish Kumar Sharma 22-07-2025
        Route::get('get_approvelessonplandata', [NewController::class, 'getApproveLessonPlandata']);
        Route::post('update_approvelessonplanstatus',[NewController::class,'UpdateApproveLessonPlanStatus']);
        
        //API for subjects and exams for classwise marks report Dev Name - Manish kumar Sharma 23-07-2025
        Route::get('get_exambyclassid',[NewController::class,'getExamByClassId']);
        Route::get('get_reportsubjectbyclasssection',[NewController::class,'getReportSubjectByClassSection']);
        
        // Api for ICICI Fee Payment Report Dev Name - Mahima Chaudhari 24-07-2025
        Route::get('geticicifeepaymentreport', [ReportController::class, 'getIciciFeePaymentReport']);
        
        //API for the Events module Dev Name - Manish Kumar Sharma 25-07-2025
        Route::post('save_event',[NewController::class,'saveEvent']);
        Route::get('get_rolesforevent',[NewController::class,'getRolesForEvent']);
        Route::post('save_savepublishevent',[NewController::class,'savePublishEvent']);
        Route::get('get_eventlist',[NewController::class,'getEventList']);
        Route::get('get_eventdata/{unq_id}',[NewController::class,'getEventData']);
        Route::post('update_publishevent',[NewController::class,'updatePublishEvent']);
        Route::delete('delete_eventbyunqid/{unq_id}',[NewController::class,'deleteEventByUnqId']);
        Route::put('update_eventbyunqid/{unq_id}',[NewController::class,'updateEventByUnqId']);
        Route::get('get_template_csv_event',[NewController::class,'getTemplateCsvEvent']);
        Route::post('import_event_csv',[NewController::class,'importEventCsv']);
        
        
        
        
        
        
        
        
        
        
        



















    });

    
    
    

//  API for the New Student list Buulk upload 
Route::get('/students/download-template/{section_id}', [AdminController::class, 'downloadCsvTemplateWithData']);
Route::post('/update-students-csv/{section_id}', [LoginController::class, 'updateCsvData']);
Route::get('/get_newstudent_by_sectionId/{section_id}', [AdminController::class, 'getNewStudentListbysectionforregister']);
Route::get('/get_all_newstudentlist', [AdminController::class, 'getAllNewStudentListForRegister']);
Route::get('/getParentInfoOfStudent/{siblingStudentId}', [AdminController::class, 'getParentInfoOfStudent']); 
Route::delete('/deleteNewstudent/{studentId}', [AdminController::class, 'deleteNewStudent']); 
Route::put('/updateNewStudent/{studentId}/{parentId}', [AdminController::class, 'updateNewStudentAndParentData']);   

//routes for the Allot Class teacher 
Route::get('/get_Classteacherslist', [AdminController::class, 'getClassteacherList']);
Route::post('/save_ClassTeacher', [AdminController::class, 'saveClassTeacher']);
Route::get('/classteacher/{class_id}/{section_id}', [AdminController::class, 'editClassTeacher']);
Route::put('/update_ClassTeacher/{class_id}/{section_id}', [AdminController::class, 'updateClassTeacher']);
Route::delete('/delete_ClassTeacher/{class_id}/{section_id}', [AdminController::class, 'deleteClassTeacher']);     
       
//routes for the Marks headings
Route::get('/get_Markheadingslist', [AssessmentController::class, 'getMarksheadingsList']);
Route::post('/save_Markheadings', [AssessmentController::class, 'saveMarksheadings']);
Route::get('/markheadings/{marks_headings_id}', [AssessmentController::class, 'editMarkheadings']);
Route::put('/update_Markheadings/{marks_headings_id}', [AssessmentController::class, 'updateMarksheadings']);
Route::delete('/delete_Markheadings/{marks_headings_id}', [AssessmentController::class, 'deleteMarksheading']);     
      
//routes for the Grades
Route::get('/get_Gradeslist', [AssessmentController::class, 'getGradesList']);
Route::post('/save_Grades', [AssessmentController::class, 'saveGrades']);
Route::get('/grades/{grade_id}', [AssessmentController::class, 'editGrades']);
Route::put('/update_Grades/{grade_id}', [AssessmentController::class, 'updateGrades']);
Route::delete('/delete_Grades/{grade_id}', [AssessmentController::class, 'deleteGrades']);   

//routes for the Exams
Route::get('/get_Term', [AssessmentController::class, 'getTerm']);
Route::get('/get_Examslist', [AssessmentController::class, 'getExamsList']);
Route::post('/save_Exams', [AssessmentController::class, 'saveExams']);
Route::get('/exams/{exam_id}', [AssessmentController::class, 'editExam']);
Route::put('/update_Exams/{exam_id}', [AssessmentController::class, 'updateExam']);
Route::delete('/delete_Exams/{exam_id}', [AssessmentController::class, 'deleteExam']);   
      
Route::post('sendnotification',[SubstituteTeacher::class,'sendNotification']);

//routes for the Allot Marks headings
// Route::get('/get_AllotMarkheadingslist/{class_id}', [AssessmentController::class, 'getAllotMarkheadingsList']);
// Route::post('/save_AllotMarkheadings', [AssessmentController::class, 'saveAllotMarkheadings']);
// Route::get('/allotmarkheadings/{allot_markheadings_id}', [AssessmentController::class, 'editAllotMarkheadings']);
// Route::put('/update_AllotMarkheadings/{allot_markheadings_id}', [AssessmentController::class, 'updateAllotMarkheadings']);
// Route::delete('/delete_AllotMarkheadings/{allot_markheadings_id}', [AssessmentController::class, 'deleteAllotMarkheading']);  
Route::get('/get_markheadingsForClassSubExam/{class_id}/{subject_id}/{exam_id}', [AssessmentController::class, 'getMarkheadingsForClassSubExam']);   
      

//Route::put('/get_sub_report_allotted/{sub_reportcard_id}', [AdminController::class, 'updateSubjectType']);

//routes for the Allot Marks headings//Hostinger Done
Route::get('/get_AllotMarkheadingslist/{class_id}', [AssessmentController::class, 'getAllotMarkheadingsList']);
Route::post('/save_AllotMarkheadings', [AssessmentController::class, 'saveAllotMarksheadings']);
Route::get('/allotmarkheadings/{allot_markheadings_id}', [AssessmentController::class, 'editAllotMarkheadings']);
Route::put('/update_AllotMarkheadings/{allot_markheadings_id}', [AssessmentController::class, 'updateAllotMarkheadings']);
Route::delete('/delete_AllotMarkheadings/{allot_markheadings_id}', [AssessmentController::class, 'deleteAllotMarksheading']); 
Route::delete('delete_AllotMarkheadingss/{class_id}/{subject_id}/{exam_id}',[AssessmentController::class,'deleteAllotMarksheadingg']);

Route::get('/clear-all', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    
    return response()->json([
        'status' => 'success',
        'message' => 'All caches cleared and optimized.',
    ]);
});

Route::get('/test-smtp', function () {
    $mailer = new SmartMailer();

    $mailer->send(
        'manishnehwal@gmail.com',
        'Test Email',
        'emails.dynamic',
        ['body' => 'This is a test.']
    );

    return '✅ Email sent!';
});
//API for the School Name Dev Name- Manish Kumar Sharma 06-05-2025
Route::get('get_schoolname',[AdminController::class,'getSchoolName']);
//API for the Forgot Password Dev Name- Manish Kumar Sharma 06-05-2025
Route::post('update_forgotpassword',[AdminController::class,'updateForgotPassword']);
Route::post('save_newpasswordforgot',[AdminController::class,'generateNewPassword']);

Route::post('sendwhatsappmessages',[AdminController::class,'sendwhatsappmessages']);

Route::post('webhook/redington', [AdminController::class, 'webhookredington']);

Route::get('whatsapp_messages_for_not_approving_lesson',[ReportController::class,'whatsappmessagesfornotapprovinglessonplan']);

// Optionally, if you need to refresh tokens
Route::post('refresh', [AuthController::class, 'refresh']);

// Example of retrieving authenticated user information
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['jwt.auth']);

    



