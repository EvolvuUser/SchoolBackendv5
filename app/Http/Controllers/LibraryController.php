<?php

namespace App\Http\Controllers;

use App\HTTP\Services\SmartMailer;
use App\Http\Services\WhatsAppService;
use App\Jobs\SendReminderRemarkJob;
use App\Jobs\IssuedBookMessageJob;
use App\Jobs\ReturnPendingBookJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use PDF;


class LibraryController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    // API method: POST /api/create-member
    public function createMembers(Request $request)
    {
        // 1) Validate: selector array aur type required hain
        $request->validate([
            'selector' => 'required|array',
            'type' => 'required|string|max:50',
        ]);

        // 2) joining_date ke liye current time
        $now = Carbon::now()->toDateTimeString();  // 'YYYY-MM-DD HH:MM:SS'

        // 3) Loop through each selected ID and insert into DB
        foreach ($request->selector as $memberId) {
            // a) prepare data
            $data = [
                'member_id' => $memberId,
                'member_type' => $request->type,
                'joining_date' => $now,
                'status' => 'A',
            ];

            // b) insert using Query Builder
            DB::table('library_member')->insert($data);
        }

        // 4) Return JSON response (API style)
        return response()->json([
            'message' => 'New Members Created Successfully!',
            'created' => count($request->selector)
        ], 201);  // 201 = created
    }

    public function getNotMembers(Request $request)
    {
        $m_type = $request->input('m_type', '');
        $class_id = $request->input('class_id', '');
        $section_id = $request->input('section_id', '');
        $name = $request->input('name', '');
        $acd_yr = $request->input('acd_yr', '');

        if ($m_type === 'S') {
            // STUDENTS (not in library_member)
            $query = DB::table('student')
                ->whereNotIn('student.student_id', function ($subquery) {
                    $subquery
                        ->select('library_member.member_id')
                        ->from('library_member')
                        ->where('library_member.member_type', '=', 'S');
                })
                ->where('student.academic_yr', $acd_yr)
                ->orderBy('student.roll_no', 'asc');

            if (!empty($class_id)) {
                $query->where('student.class_id', $class_id);
            }

            if (!empty($section_id)) {
                $query->where('student.section_id', $section_id);
            }

            if (!empty($name)) {
                $names = explode(' ', $name);
                $fname = $names[0];
                $lname = $names[1] ?? '';
                $query->where('student.first_name', 'like', "%$fname%");

                if ($lname) {
                    $query->where('student.last_name', 'like', "%$lname%");
                }
            }

            $result = $query->get();

            return response()->json($result);
        }

        if ($m_type === 'T') {
            // TEACHERS (not in library_member)
            $query = DB::table('teacher')
                ->whereNotIn('teacher.teacher_id', function ($subquery) {
                    $subquery
                        ->select('library_member.member_id')
                        ->from('library_member')
                        ->where('library_member.member_type', '=', 'T');
                });

            if (!empty($name)) {
                $query
                    ->where('teacher.name', 'like', "%$name%")
                    ->orderBy('teacher.name', 'asc');
            }

            $result = $query->get();

            return response()->json($result);
        }

        return response()->json(['error' => 'Invalid member type'], 400);
    }

    // 1) GET /api/category-group
    public function index()
    {
        $rows = DB::table('category_group')
            ->orderBy('category_group_id', 'DESC')
            ->get();
        return response()->json($rows, 200);
    }

    // 2) POST /api/category-group  -> create
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:30|unique:category_group,category_group_name'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $id = DB::table('category_group')->insertGetId([
            'category_group_name' => $request->input('name')
        ]);

        return response()->json([
            'message' => 'New category group created!',
            'category_group_id' => $id
        ], 201);
    }

    // 3) PUT /api/category-group/{id} -> update
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            // exclude current record from unique check
            'name' => "required|string|max:30|unique:category_group,category_group_name,{$id},category_group_id"
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = DB::table('category_group')
            ->where('category_group_id', $id)
            ->update(['category_group_name' => $request->input('name')]);

        if ($updated) {
            return response()->json(['message' => 'Category group updated!'], 200);
        }

        return response()->json(['message' => 'No changes made or record not found'], 404);
    }

    // 4) DELETE /api/category-group/{id} -> delete (with 'in use' check)
    public function destroy($id)
    {
        // check if in use in category_categorygroup (same check as CodeIgniter)
        $inUse = DB::table('category_categorygroup')->where('category_group_id', $id)->exists();

        if ($inUse) {
            return response()->json(['error' => 'This Category Group Name is in use. Delete failed!!!'], 400);
        }

        $deleted = DB::table('category_group')->where('category_group_id', $id)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Category group deleted!'], 200);
        }

        return response()->json(['error' => 'Record not found'], 404);
    }

    // 5) GET /api/category-group/names -> for autocomplete (label/value)
    public function names()
    {
        $data = DB::table('category_group')->select('category_group_id', 'category_group_name')->get();

        $result = $data->map(function ($row) {
            return [
                'label' => $row->category_group_name,
                'value' => $row->category_group_id
            ];
        });

        return response()->json($result, 200);
    }

    public function getLibraryCategory()
    {
        // Fetch all category records from the database
        $data = DB::table('category')->get();

        // Format the data to include 'label' and 'value' keys
        $result = $data->map(function ($item) {
            return [
                'label' => $item->call_no . ' / ' . $item->category_name,
                'value' => $item->category_id
            ];
        });

        // Return as JSON response
        return response()->json($result, 200);
    }

    public function Librarystore(Request $request)
    {
        // Validation
        // Step 1: Validation
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:100',
            'call_no' => 'required|string|max:50',
            // category_group_ids ab optional hai
            'category_group_ids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Step 2: Insert into category table
        $categoryId = DB::table('category')->insertGetId([
            'category_name' => $request->category_name,
            'call_no' => $request->call_no,
        ]);

        // Step 3: Agar group IDs diye gaye hain to hi insert karo
        if ($request->has('category_group_ids') && !empty($request->category_group_ids)) {
            foreach ($request->category_group_ids as $groupId) {
                DB::table('category_categorygroup')->insert([
                    'category_id' => $categoryId,
                    'category_group_id' => $groupId,
                ]);
            }
        }

        return response()->json([
            'message' => 'New category created successfully!',
            'category_id' => $categoryId
        ], 201);
    }

    public function Libraryupdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:100',
            'call_no' => 'required|string|max:50',
            'category_group_ids' => 'array|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $id) {
            // 1️⃣ Update category
            DB::table('category')
                ->where('category_id', $id)
                ->update([
                    'category_name' => $request->category_name,
                    'call_no' => $request->call_no,
                ]);

            // 2️⃣ ALWAYS delete old mappings (CI behavior)
            DB::table('category_categorygroup')
                ->where('category_id', $id)
                ->delete();

            // 3️⃣ Insert only if groups are provided
            if (!empty($request->category_group_ids)) {
                foreach ($request->category_group_ids as $groupId) {
                    if ($groupId) {
                        DB::table('category_categorygroup')->insert([
                            'category_id' => $id,
                            'category_group_id' => $groupId,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Category edited!!!'
        ], 200);
    }

    public function Librarydestroy($id)
    {
        // Step 1: Check if category used in books
        $inUse = DB::table('book')->where('category_id', $id)->exists();
        if ($inUse) {
            return response()->json(['error' => 'This category is in use. Delete failed!'], 400);
        }

        // Step 2: Delete from category_categorygroup
        DB::table('category_categorygroup')->where('category_id', $id)->delete();

        // Step 3: Delete from category
        $deleted = DB::table('category')->where('category_id', $id)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Category deleted successfully!'], 200);
        }

        return response()->json(['error' => 'Category not found!'], 404);
    }

    public function showCategoryGroupById($id)
    {
        $category = DB::table('category')->where('category_id', $id)->first();

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $groupIds = DB::table('category_categorygroup')
            ->where('category_id', $id)
            ->pluck('category_group_id');  // returns an array of IDs

        $response = [
            'category_id' => $category->category_id,
            'category_name' => $category->category_name,
            'call_no' => $category->call_no,
            'category_groups' => $groupIds,
        ];

        return response()->json($response, 200);
    }

    // Old
    // public function getBookDetails(Request $request)
    // {
    //     $book_id = $request->input('book_id');

    //     // ✅ Direct Query Builder Join
    //     $bookData = DB::table('book')
    //         ->join('book_copies', 'book.book_id', '=', 'book_copies.book_id')
    //         ->where('book.book_id', $book_id)
    //         ->select(
    //             'book.book_id',
    //             'book.book_title',
    //             'book.category_id',
    //             'book.author',
    //             'book.publisher',
    //             'book.days_borrow',
    //             'book.location_of_book',
    //             'book.issue_type',
    //             'book_copies.book_copies_id',
    //             'book_copies.copy_id',
    //             'book_copies.bill_no',
    //             'book_copies.source_of_book',
    //             'book_copies.isbn',
    //             'book_copies.year',
    //             'book_copies.edition',
    //             'book_copies.no_of_pages',
    //             'book_copies.price',
    //             'book_copies.added_date',
    //             'book_copies.status',
    //             'book_copies.IsNew'
    //         )
    //         ->get();

    //     if (empty($bookData)) {
    //         return response()->json(['message' => 'No records found'], 404);
    //     }

    //     return response()->json(['data' => $bookData], 200);
    // }

    public function getBookDetails(Request $request)
    {
        $book_id = $request->query('book_id');
        $status = $request->query('status') ?? '';

        // ✅ Direct Query Builder Join
        $bookData = DB::table('book')
            ->where('book.book_id', $book_id)
            ->select('book.*')
            ->get();

        if (empty($bookData)) {
            return response()->json(['message' => 'No records found'], 404);
        }

        $bookCopyData = DB::table('book_copies')
            ->where('book_id', $book_id)
            ->get();

        if ($status != '') {
            if ($status == 'I') {
                $this->db->where_not_in('status', $status);
            } else {
                $this->db->where('status', $status);
            }
        }

        return response()->json([
            'book-details' => $bookData,
            'book-copy-details' => $bookCopyData,
        ], 200);
    }

    public function searchBooks(Request $request)
    {
        try {
            $status = $request->input('status');
            $category_group_id = $request->input('category_group_id');
            $category_id = $request->input('category_id');
            $author = $request->input('author');
            $title = $request->input('title');
            $isNew = $request->input('is_new');
            $accession_no = $request->input('accession_no');

            $query = DB::table('book')
                ->leftJoin('book_copies', 'book.book_id', '=', 'book_copies.book_id')
                ->join('category', 'category.category_id', '=', 'book.category_id')
                ->select(
                    'book.book_id',
                    'book.book_title',
                    'book.author',
                    'book.publisher',
                    'book.category_id',
                    'category.category_name',
                    'category.call_no',
                    'book_copies.copy_id as accession_no',
                    DB::raw('COUNT(book_copies.copy_id) AS total_copies')
                )
                ->groupBy('book.book_id');

            if ($accession_no) {
                $query->where('book_copies.copy_id', $accession_no);
            }

            if (!empty($status)) {
                $query->where('book_copies.status', $status);
            }

            if (!empty($category_group_id)) {
                $query
                    ->join('category_categorygroup as b', 'category.category_id', '=', 'b.category_id')
                    ->where('b.category_group_id', $category_group_id);
            }

            if (!empty($category_id)) {
                $query->where('book.category_id', $category_id);
            }

            if (!empty($author)) {
                $query->where('book.author', 'like', '%' . $author . '%');
            }

            if (!empty($title)) {
                $query->where('book.book_title', 'like', '%' . $title . '%');
            }

            if (!empty($isNew) && $isNew == true) {
                $query->whereRaw('DATEDIFF(NOW(), book_copies.added_date) <= 60');
            }

            $books = $query->get();

            if ($books->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No books found.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Books fetched successfully.',
                'data' => $books,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function editBook(Request $request, $book_id)
    {
        try {
            if ($request->input('operation') === 'edit') {
                $copyIds = $request->input('copyedit');  // accession numbers
                $copyRowIds = $request->input('book_copies_id');  // book_copies_id
                $source = $request->input('source');
                $bill = $request->input('bill_no');
                $isbn = $request->input('isbn');
                $edition = $request->input('edition');
                $price = $request->input('price');
                $no_of_pages = $request->input('no_of_pages');
                $year = $request->input('year');
                $status = $request->input('status');

                // 🔹 MAIN BOOK DATA (MATCHED WITH CI)
                $bookData = [
                    'book_title' => $request->input('book_title'),
                    'author' => $request->input('author'),
                    'category_id' => $request->input('category_id'),
                    'publisher' => $request->input('publisher'),
                    'days_borrow' => $request->input('days_borrow'),
                    'location_of_book' => $request->input('location_of_book'),
                    'issue_type' => $request->input('issue_type'),
                ];

                DB::table('book')
                    ->where('book_id', $book_id)
                    ->update($bookData);

                // 🔹 BOOK COPIES LOOP
                for ($i = 0; $i < count($copyRowIds); $i++) {
                    $copyData = [
                        'book_id' => $book_id,
                        'status' => 'A',
                        'copy_id' => $copyIds[$i],
                        'source_of_book' => $source[$i],
                        'bill_no' => $bill[$i],
                        'isbn' => $isbn[$i],
                        'edition' => $edition[$i],
                        'price' => $price[$i],
                        'no_of_pages' => $no_of_pages[$i],
                        'year' => $year[$i],
                        'added_date' => now(),
                    ];

                    // 🔹 INSERT
                    if ($copyRowIds[$i] == '0') {
                        $exists = DB::table('book_copies')
                            ->where('copy_id', $copyIds[$i])
                            ->exists();

                        if ($exists) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Book for this accession no. already exists!'
                            ], 409);
                        }

                        DB::table('book_copies')->insert($copyData);
                    }
                    // 🔹 UPDATE
                    else {
                        DB::table('book_copies')
                            ->where('book_copies_id', $copyRowIds[$i])
                            ->update([
                                'status' => $status[$i],
                                'source_of_book' => $source[$i],
                                'bill_no' => $bill[$i],
                                'isbn' => $isbn[$i],
                                'edition' => $edition[$i],
                                'price' => $price[$i],
                                'no_of_pages' => $no_of_pages[$i],
                                'year' => $year[$i],
                            ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Book Updated Successfully!'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid operation.'
            ], 400);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteBook($book_id)
    {
        try {
            // Match CI logic: ANY issue_return record blocks delete
            $exists = DB::table('issue_return')
                ->where('book_id', $book_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Book is issued. Delete failed.'
                ], 409);
            }

            DB::table('book_copies')
                ->where('book_id', $book_id)
                ->delete();

            DB::table('book')
                ->where('book_id', $book_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createBook(Request $request)
    {
        try {
            if ($request->input('operation') == 'create') {
                // Step 1: Insert into book table
                $data = [
                    'book_title' => $request->input('book_title'),
                    'category_id' => $request->input('category_id'),
                    'author' => $request->input('author'),
                    'publisher' => $request->input('publisher'),
                    'days_borrow' => $request->input('days_borrow'),
                    'location_of_book' => $request->input('location_of_book'),
                    'issue_type' => $request->input('issue_type'),
                ];

                DB::table('book')->insert($data);
                $book_id = DB::getPdo()->lastInsertId();

                // Step 2: Read arrays from request
                $copies = $request->input('copy');
                $sources = $request->input('source');
                $bill_no = $request->input('bill_no');
                $isbn = $request->input('isbn');
                $edition = $request->input('edition');
                $price = $request->input('price');
                $no_of_pages = $request->input('no_of_pages');
                $year = $request->input('year');

                // Step 3: Loop through all copies
                for ($i = 0; $i < count($copies); $i++) {
                    // Check duplicate accession number
                    $exists = DB::table('book_copies')->where('copy_id', $copies[$i])->exists();

                    if ($exists) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Book for this accession no. already exists!'
                        ], 409);
                    } else {
                        $data1 = [
                            'book_id' => $book_id,
                            'copy_id' => $copies[$i],
                            'source_of_book' => $sources[$i],
                            'bill_no' => $bill_no[$i],
                            'isbn' => $isbn[$i],
                            'edition' => $edition[$i],
                            'price' => $price[$i],
                            'no_of_pages' => $no_of_pages[$i],
                            'year' => $year[$i],
                            'status' => 'A',
                            'added_date' => now(),
                        ];

                        DB::table('book_copies')->insert($data1);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'New Book created!',
                    'book_id' => $book_id
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid operation.'
            ], 400);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMaxCopyId()
    {
        $result = DB::table('book_copies')
            ->select(DB::raw('MAX(CAST(copy_id AS UNSIGNED)) as copy_id'))
            ->first();

        if ($result && $result->copy_id) {
            return response()->json([
                'success' => true,
                'max_copy_id' => $result->copy_id
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No records found in book_copies table.'
            ]);
        }
    }

    public function getLibraryMembersInfo(Request $request)
    {
        $user = $this->authenticateUser();
        $acd_yr = JWTAuth::getPayload()->get('academic_year');

        $m_type = $request->input('m_type', '');
        $class_id = $request->input('class_id', '');
        $section_id = $request->input('section_id', '');
        $name = $request->input('name', '');

        $status = $request->input('status', '');
        $grn_no = $request->input('grn_no', '');

        if ($m_type === 'S') {
            $query = DB::table('student')
                ->leftjoin('library_member', 'student.student_id', '=', 'library_member.member_id')
                ->leftjoin('class', 'student.class_id', '=', 'class.class_id')
                ->leftjoin('section', 'section.section_id', '=', 'student.section_id')
                ->select('student.*', 'library_member.*', 'class.name as class_name', 'section.name as section_name')
                ->where('library_member.member_type', $m_type);

            if (!empty($grn_no)) {
                $query->where('student.reg_no', $grn_no);
            }

            if (!empty($class_id)) {
                $query->where('student.class_id', $class_id);
            }

            if (!empty($section_id)) {
                $query->where('student.section_id', $section_id);
            }

            if (!empty($name)) {
                $fname = substr($name, 0, strpos($name, ' '));
                $lname = substr($name, strpos($name, ' ') + 1);
                $query->where('student.first_name', 'like', "%{$fname}%");
            }

            if (!empty($acd_yr)) {
                $query->where('student.academic_yr', $acd_yr);
            }

            if (!empty($status)) {
                $query->where('library_member.status', 'like', "%{$status}%");
            }

            $data = $query->get();
        } elseif ($m_type === 'T') {
            $query = DB::table('teacher')
                ->join('library_member', 'teacher.teacher_id', '=', 'library_member.member_id')
                ->select('teacher.*', 'library_member.*')
                ->where('library_member.member_type', $m_type);

            if (!empty($name)) {
                $query->where('teacher.name', 'like', "%{$name}%");
            }

            if (!empty($status)) {
                $query->where('library_member.status', 'like', "%{$status}%");
            }

            $data = $query->get();
        } else {
            return response()->json(['error' => 'Invalid member type'], 400);
        }

        return response()->json($data);
    }

    public function updateLibraryMemberStatus(Request $request)
    {
        $status_action = $request->input('action', '');  // 'Active' or 'Inactive'
        $member_id = $request->input('member_id', '');  // student_id or teacher_id
        $member_type = $request->input('member_type', '');  // 'S' or 'T'

        if (empty($status_action) || empty($member_id) || empty($member_type)) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        // 🔹 Case 1: Make Inactive (check if books are issued)
        if ($status_action === 'Inactive') {
            $issuedBooks = DB::table('issue_return')
                ->where('member_id', $member_id)
                ->where('member_type', $member_type)
                ->where('return_date', '0000-00-00')
                ->count();

            if ($issuedBooks > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This member cannot be made inactive because books are currently issued to them.'
                ], 400);
            }

            // No active book issue → make inactive
            DB::table('library_member')
                ->where('member_id', $member_id)
                ->where('member_type', $member_type)
                ->update(['status' => 'I']);

            return response()->json([
                'status' => 'success',
                'message' => 'Member is made Inactive!'
            ]);
        }

        // 🔹 Case 2: Make Active
        if ($status_action === 'Active') {
            DB::table('library_member')
                ->where('member_id', $member_id)
                ->where('member_type', $member_type)
                ->update(['status' => 'A']);

            return response()->json([
                'status' => 'success',
                'message' => 'Member is made Active!'
            ]);
        }

        return response()->json(['error' => 'Invalid action. Use Active or Inactive'], 400);
    }

    // Generate Barcode
    public function getAccessionNoFromAndTo(Request $request)
    {
        $copy_id_from = $request->input('copy_id_from');
        $copy_id_to = $request->input('copy_id_to');

        $query = DB::table('book_copies')->select('copy_id');

        if (!empty($copy_id_from) && !empty($copy_id_to)) {
            $query->whereRaw(
                'CAST(copy_id AS DECIMAL(10,1)) BETWEEN ? AND ?',
                [$copy_id_from, $copy_id_to]
            );
        } elseif (!empty($copy_id_from)) {
            $query->whereRaw(
                'CAST(copy_id AS DECIMAL(10,1)) = ?',
                [$copy_id_from]
            );
        } elseif (!empty($copy_id_to)) {
            $query->whereRaw(
                'CAST(copy_id AS DECIMAL(10,1)) = ?',
                [$copy_id_to]
            );
        }

        $query->orderByRaw('CAST(copy_id AS DECIMAL(10,1)) ASC');

        $result = $query->get();

        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }

    // Issue Book 20-11-2025
    public function getLibraryIssuedMembers(Request $request)
    {
        $validated = $request->validate([
            'mtype' => 'required|in:S,T',
            'class_id' => 'nullable|integer',
            'section_id' => 'nullable|integer'
        ]);

        $result = getIssuedMembers(
            $validated['mtype'],
            $validated['class_id'] ?? null,
            $validated['section_id'] ?? null
        );

        return response()->json($result);
    }

    // library/issued_books
    public function getIssuedBooksByMember(Request $request)
    {
        $memberId = $request->input('member_id');
        $grn_no = $request->input('grn_no');
        $mtype = $request->input('mtype');

        $academicYr = JWTAuth::getPayload()->get('academic_year');

        if (!$memberId && !$grn_no) {
            return response()->json([
                'status' => false,
                'message' => 'Member ID Or GRN No is required'
            ], 403);
        }

        $issuedBooks = null;

        // $memberExists = DB::table('library_member')
        //     ->where('member_id', $memberId)
        //     ->where('member_type', $mtype)
        //     ->exists();

        // if (!$memberExists) {
        //     return response()->json([
        //         'status'  => false,
        //         'message' => 'This is not a library member'
        //     ], 404);
        // }

        $member = null;

        if ($memberId) {

            $memberExists = DB::table('library_member')
                ->where('member_id', $memberId)
                ->where('member_type', $mtype)
                ->exists();

            // $query = DB::table('library_member')
            //     ->where('member_id', $memberId)
            //     ->where('member_type', $mtype);

            if (!$memberExists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This is not a library member',
                    // 'query' => $query->toSql(), 
                    // 'queryBinding' => $query->getBindings()
                ], 404);
            }

            if ($mtype == 'S') {
                $member = DB::table("student")->where('student_id', $memberId)->first();
            } else {
                $member = DB::table("teacher")->where('teacher_id', $memberId)->first();
            }

            $issuedBooks = DB::table('book_copies as d')
                ->join('book as b', 'b.book_id', '=', 'd.book_id')
                ->join('issue_return as a', 'a.copy_id', '=', 'd.copy_id')
                ->join('category as c', 'c.category_id', '=', 'b.category_id')
                ->select(
                    'a.member_id',
                    'd.copy_id',
                    DB::raw("DATE_FORMAT(a.issue_date, '%d-%m-%Y') as issue_date"),
                    DB::raw("DATE_FORMAT(a.due_date, '%d-%m-%Y') as due_date"),
                    'b.book_title',
                    'b.author',
                    'c.category_name',
                    'd.status'
                )
                ->where('a.member_id', $memberId)
                ->where('d.status', 'I')
                ->where('a.return_date', '0000-00-00')
                ->get();
        } else if ($grn_no) {

            $student = DB::table('student')
                ->where('reg_no', $grn_no)
                ->where('isDelete', 'N')
                ->where('academic_yr', $academicYr)
                ->where('parent_id', '!=', '0')
                ->first();

            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid GRN number',
                ], 404);
            }

            $memberExists = DB::table('student')
                ->join('library_member', 'student.student_id', '=', 'library_member.member_id')
                ->where('library_member.member_type', 'S')
                ->where('library_member.status', 'A')
                ->where('student.student_id', $student->student_id)
                ->where('student.academic_yr', $academicYr)->exists();

            if (!$memberExists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This is not a library member'
                ], 404);
            }

            $member = $student;

            $issuedBooks = DB::table('book_copies as d')
                ->join('book as b', 'b.book_id', '=', 'd.book_id')
                ->join('issue_return as a', 'a.copy_id', '=', 'd.copy_id')
                ->join('category as c', 'c.category_id', '=', 'b.category_id')
                ->join('student as e', 'e.student_id', '=', 'a.member_id')
                ->select(
                    'a.member_id',
                    'a.copy_id',
                    'a.return_date',
                    DB::raw("DATE_FORMAT(a.issue_date, '%d-%m-%Y') as issue_date"),
                    DB::raw("DATE_FORMAT(a.due_date, '%d-%m-%Y') as due_date"),
                    'b.book_title',
                    'b.author',
                    'c.category_name',
                    'd.copy_id',
                    'd.status'
                )
                ->where('e.reg_no', $grn_no)
                ->where('d.status', 'I')
                ->where('a.return_date', '0000-00-00')
                ->get();
        }

        // if (count($issuedBooks) == 0) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'This is not a library member',
        //     ], 404);
        // }

        return response()->json([
            'status' => true,
            'data' => $issuedBooks,
            'member' => $member,
        ], 200);
    }


    public function getBookByAccession(Request $request)
    {
        $copyId = $request->input('copy_id');

        if (!$copyId) {
            return response()->json(['error' => 'copy_id is required'], 400);
        }

        $data = DB::table('book_copies as bc')
            ->join('book as b', 'b.book_id', '=', 'bc.book_id')
            ->join('category as c', 'c.category_id', '=', 'b.category_id')
            ->where('bc.copy_id', $copyId)
            ->select(
                'bc.copy_id',
                'bc.status',
                'b.issue_type',  // ✔ FIXED
                'b.book_id',
                'b.book_title',
                'c.category_id',
                'c.category_name'
            )
            ->get();

        return response()->json($data);
    }

    public function getDueDate($memberType, $issueDate)
    {
        if (!in_array($memberType, ['S', 'T'])) {
            return response()->json(['error' => 'Invalid member type'], 400);
        }

        try {
            if ($memberType == 'S') {
                $days = 7;
            } else {
                $days = 30;
            }

            $dueDate = date('d-m-Y', strtotime($issueDate . " +$days days"));

            return response()->json(['due_date' => $dueDate]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }
    }

    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    // public function issueBook(Request $request)
    // {
    //     $user = $this->authenticateUser();
    //     $academic_yr = JWTAuth::getPayload()->get('academic_year');

    //     $request->validate([
    //         'issueddate' => 'required|date',
    //         'copy_id' => 'required|array',
    //         'book_id' => 'required|array',
    //         'member_type' => 'required|string',
    //     ]);

    //     $memberType = $request->member_type;
    //     $issueDate = date('Y-m-d', strtotime($request->issueddate));

    //     // grn no
    //     if ($request->grn_no != '') {
    //         $student = DB::table('student')
    //             ->where('reg_no', $request->grn_no)
    //             ->where('academic_yr', $academic_yr)
    //             ->first();

    //         if (!$student) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Invalid GRN Number'
    //             ], 404);
    //         }

    //         $memberId = $student->student_id;
    //     }
    //     // member_id
    //     else {
    //         $memberId = $request->member_id;
    //     }

    //     // check libarary member
    //     $memberCheck = DB::table('library_member')
    //         ->where('member_id', $memberId)
    //         ->exists();

    //     if (!$memberCheck) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Not a valid library member'
    //         ], 403);
    //     }

    //     // check duplicate copy_id
    //     if (count($request->copy_id) !== count(array_unique($request->copy_id))) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Duplicate book copies cannot be issued'
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         foreach ($request->copy_id as $i => $copyId) {
    //             $bookId = $request->book_id[$i];

    //             // calculate due date
    //             if ($memberType == 'S') {
    //                 $dueDate = date('Y-m-d H:i:s', strtotime($issueDate . '+7 days'));
    //             } else {
    //                 $dueDate = date('Y-m-d H:i:s', strtotime($issueDate . '+30 days'));
    //             }

    //             // new entry of issue book
    //             DB::table('issue_return')->insert([
    //                 'member_type' => $memberType,
    //                 'member_id' => $memberId,
    //                 'book_id' => $bookId,
    //                 'copy_id' => $copyId,
    //                 'issue_date' => $issueDate,
    //                 'due_date' => $dueDate,
    //             ]);

    //             // update book status
    //             DB::table('book_copies')
    //                 ->where('copy_id', $copyId)
    //                 ->where('book_id', $bookId)
    //                 ->update([
    //                     'status' => 'I'  // I = Issued
    //                 ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Books issued successfully'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Error issuing book',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // Changed by mahima 12-02-2026  Whats app integration
    public function issueBook(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $settingsData = getSchoolSettingsData();
        $schoolName = $settingsData->institute_name;
        $defaultPassword = $settingsData->default_pwd;
        $websiteUrl = $settingsData->website_url;
        $shortName = $settingsData->short_name;
        $whatsappIntegration = $settingsData->whatsapp_integration;
        $smsIntegration = $settingsData->sms_integration;
        $savepublish = 'Y';

        $request->validate([
            'issueddate' => 'required|date',
            'copy_id' => 'required|array',
            'book_id' => 'required|array',
            'member_type' => 'required|string',
        ]);

        $memberType = $request->member_type;
        $issueDate = date('Y-m-d', strtotime($request->issueddate));

        // grn no
        if ($request->grn_no != '') {
            $student = DB::table('student')
                ->where('reg_no', $request->grn_no)
                ->where('academic_yr', $academic_yr)
                ->first();

            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid GRN Number'
                ], 404);
            }

            $memberId = $student->student_id;
        }
        // member_id
        else {
            $memberId = $request->member_id;
        }

        // check libarary member
        $memberCheck = DB::table('library_member')
            ->where('member_id', $memberId)
            ->exists();

        if (!$memberCheck) {
            return response()->json([
                'status' => false,
                'message' => 'Not a valid library member'
            ], 403);
        }

        // check duplicate copy_id
        if (count($request->copy_id) !== count(array_unique($request->copy_id))) {
            return response()->json([
                'status' => false,
                'message' => 'Duplicate book copies cannot be issued'
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->copy_id as $i => $copyId) {
                $bookId = $request->book_id[$i];

                // calculate due date
                if ($memberType == 'S') {
                    $dueDate = date('Y-m-d H:i:s', strtotime($issueDate . '+7 days'));
                } else {
                    $dueDate = date('Y-m-d H:i:s', strtotime($issueDate . '+30 days'));
                }

                // new entry of issue book
                DB::table('issue_return')->insert([
                    'member_type' => $memberType,
                    'member_id' => $memberId,
                    'book_id' => $bookId,
                    'copy_id' => $copyId,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                ]);

                // update book status
                DB::table('book_copies')
                    ->where('copy_id', $copyId)
                    ->where('book_id', $bookId)
                    ->update([
                        'status' => 'I'  // I = Issued
                    ]);

                IssuedBookMessageJob::dispatch([
                    'member_id'     => $memberId,
                    'member_type'   => $memberType,
                    'book_id'       => $bookId,
                    'copy_id'       => $copyId,
                    'academic_year' => $academic_yr,
                    'issue_date'    => $issueDate,
                    'due_date'      => $dueDate,
                ])->afterCommit();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Books issued successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error issuing book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Return Book 25-11-2025
    public function getMembersForIssuedBook(Request $request)
    {
        $type = $request->input('type');
        $class_id = $request->input('class_id');
        $section_id = $request->input('section_id');

        $data = $this->getIssueReturnBooks($type, $class_id, $section_id, '');

        foreach ($data as $row) {
            if ($row->member_type == 'T') {
                $row->label = $row->name;
                $row->value = $row->teacher_id;
            } else {
                $rollData = ($row->roll_no == null || $row->roll_no == '' || $row->roll_no == 0)
                    ? ''
                    : '(Roll No - ' . $row->roll_no . ')';

                $row->label = trim($row->first_name . ' ' . $row->mid_name . ' ' . $row->last_name . ' ' . $rollData);
                $row->value = $row->student_id;
            }
        }

        return response()->json($data);
    }

    private function getIssueReturnBooks($m_type, $class_id, $section_id, $name)
    {
        if ($m_type === 'S') {
            return DB::table('issue_return as a')
                ->select(
                    'a.member_id',
                    'a.copy_id',
                    'a.member_type',
                    'b.*',
                    'd.copy_id'
                )
                ->join('student as b', 'a.member_id', '=', 'b.student_id')
                ->join('book_copies as d', 'a.copy_id', '=', 'd.copy_id')
                ->where('a.member_type', $m_type)
                ->when($class_id, fn($q) => $q->where('b.class_id', $class_id))
                ->when($section_id, fn($q) => $q->where('b.section_id', $section_id))
                ->when($name, fn($q) => $q->where('b.first_name', 'LIKE', '%' . $name . '%'))
                ->where('a.return_date', '0000-00-00')
                ->groupBy('a.member_id')
                ->get();
        }

        if ($m_type === 'T') {
            return DB::table('issue_return as a')
                ->select(
                    'a.member_id',
                    'a.copy_id',
                    'a.member_type',
                    'b.*',
                    'd.copy_id'
                )
                ->join('teacher as b', 'a.member_id', '=', 'b.teacher_id')
                ->join('book_copies as d', 'a.copy_id', '=', 'd.copy_id')
                ->where('a.member_type', $m_type)
                ->when($name, fn($q) => $q->where('b.name', 'LIKE', '%' . $name . '%'))
                ->where('a.return_date', '0000-00-00')
                ->groupBy('a.member_id')
                ->get();
        }

        return [];
    }

    // OLD
    // public function BooksIssueAPI(Request $request)
    // {
    //     $type = $request->query('type');

    //     if (!$type) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => "Missing 'type' parameter"
    //         ], 400);
    //     }

    //     /* 1)  getMemberOnAccession */
    //     if ($type === 'accession') {
    //         $copy_id = $request->query('copy_id');

    //         if (!$copy_id) {
    //             return response()->json(['error' => 'copy_id required'], 400);
    //         }

    //         $row = DB::table('issue_return')
    //             ->select('member_id')
    //             ->where('copy_id', $copy_id)
    //             ->where('return_date', '0000-00-00')
    //             ->first();

    //         return response()->json($row ? $row : null, $row ? 200 : 204);
    //     }

    //     /* 2)  getMemberOnGrno */
    //     if ($type === 'grno') {
    //         $reg_no = $request->query('reg_no');

    //         if (!$reg_no) {
    //             return response()->json(['error' => 'reg_no required'], 400);
    //         }

    //         $row = DB::table('issue_return as a')
    //             ->join('student as b', 'a.member_id', '=', 'b.student_id')
    //             ->select('a.member_id')
    //             ->where('b.reg_no', $reg_no)
    //             ->where('a.return_date', '0000-00-00')
    //             ->first();

    //         return response()->json($row ? $row : null, $row ? 200 : 204);
    //     }

    //     /* 3)  getIssueReturn */
    //     if ($type === 'records') {
    //         $m_type = $request->query('m_type');
    //         $member_id = $request->query('member_id');

    //         $q = DB::table('issue_return')->select('*');

    //         if (!empty($member_id)) {
    //             $q->where('issue_return.member_id', $member_id);
    //         }
    //         if (!empty($m_type)) {
    //             $q->where('issue_return.member_type', $m_type);
    //         }

    //         $rows = $q->where('issue_return.return_date', '0000-00-00')->get();

    //         return response()->json($rows, 200);
    //     }

    //     /*
    //      * 4) getMemDataTypeStudent
    //      */

    //     if ($type === 'student') {
    //         $copy_id = $request->query('copy_id');
    //         $acd_yr = $request->query('acd_yr');
    //         $grn_no = $request->query('grn_no');

    //         $q = DB::table('issue_return as a')
    //             ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
    //             ->join('student as d', 'a.member_id', '=', 'd.student_id')
    //             ->select(
    //                 'a.*',
    //                 'b.copy_id as copy_id',
    //                 'b.status as copy_status',
    //                 'd.first_name',
    //                 'd.mid_name',
    //                 'd.last_name',
    //                 'd.roll_no',
    //                 'd.class_id',
    //                 'd.section_id',
    //                 'd.academic_yr',
    //                 'd.reg_no'
    //             );

    //         if (!empty($grn_no)) {
    //             $q->where('d.reg_no', $grn_no);
    //         } else {
    //             if (!$copy_id) {
    //                 return response()->json(['error' => 'copy_id required'], 400);
    //             }

    //             $q
    //                 ->where('a.copy_id', $copy_id)
    //                 ->where('a.return_date', '0000-00-00')
    //                 ->where('b.status', 'I');

    //             if (!empty($acd_yr)) {
    //                 $q->where('d.academic_yr', $acd_yr);
    //             }
    //         }

    //         return response()->json($q->get(), 200);
    //     }

    //     /* 5) getMemDataTypeStaff */
    //     if ($type === 'staff') {
    //         $copy_id = $request->query('copy_id');

    //         if (!$copy_id) {
    //             return response()->json(['error' => 'copy_id required'], 400);
    //         }

    //         $rows = DB::table('issue_return as a')
    //             ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
    //             ->join('teacher as e', 'a.member_id', '=', 'e.teacher_id')
    //             ->select(
    //                 'a.*',
    //                 'b.copy_id as copy_id',
    //                 'b.status as copy_status',
    //                 'e.name as teacher_name'
    //             )
    //             ->where('a.copy_id', $copy_id)
    //             ->where('a.return_date', '0000-00-00')
    //             ->where('b.status', 'I')
    //             ->get();

    //         return response()->json($rows, 200);
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'Invalid type. Allowed values: accession, grno, records, student, staff'
    //     ], 400);
    // }

    private function getMemberDetails($grn_no, $acd_yr, $copy_id, $m_type)
    {
        $data = null;
        $member_id = null;
        $member = null;
        if (!$m_type) {
            $member_id = DB::table('issue_return')
                ->select('member_id')
                ->where('copy_id', $copy_id)
                ->where('return_date', '0000-00-00')
                ->first()
                ->member_id ?? null;

            if ($member_id == null) {
                return false;
            }

            $member = DB::table('library_member')->where('member_id', $member_id)->first();
            $m_type = $member->member_type;
        }

        // Find out member details
        if ($m_type == 'S') {
            $query = DB::table('issue_return as a')
                ->select(
                    'a.*',
                    'b.copy_id',
                    'b.status',
                    'd.first_name',
                    'd.roll_no',
                    'd.mid_name',
                    'd.last_name',
                    'd.class_id',
                    'd.section_id',
                    'd.academic_yr',
                    'd.reg_no'
                )
                ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
                ->join('student as d', 'a.member_id', '=', 'd.student_id');

            if (!empty($grn_no)) {
                $query->where('d.reg_no', $grn_no);
            } else {
                $query
                    ->where('a.copy_id', $copy_id)
                    ->where('a.return_date', '0000-00-00')
                    ->where('b.status', 'I')
                    ->where('d.academic_yr', $acd_yr);
            }
            $data = $query->first();
        } else if ($m_type == 'T') {
            $data = DB::table('issue_return as a')
                ->select(
                    'a.*',
                    'b.copy_id',
                    'b.status',
                    'e.name'
                )
                ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
                ->join('teacher as e', 'a.member_id', '=', 'e.teacher_id')
                ->where('a.copy_id', $copy_id)
                ->where('a.return_date', '0000-00-00')
                ->where('b.status', 'I')
                ->first();
        }

        // Find out Book details
        $query = DB::table('issue_return')
            ->select(
                'issue_return.*',
                'book.book_title',
            );

        $query->leftJoin('book', 'book.book_id', '=', 'issue_return.book_id');

        if (!empty($member_id)) {
            $query->where('issue_return.member_id', $member_id);
        }
        if (!empty($m_type)) {
            $query->where('issue_return.member_type', $m_type);
        }
        $query->where('issue_return.return_date', '0000-00-00');
        $bookDetails = $query->get();
        return [
            'member' => $data,
            'book' => $bookDetails,
        ];
    }

    // private function getMemberDetailsUsingSearch($m_type, $member_id, $acd_yr)
    // {
    //     $data = null;
    //     $member = DB::table('library_member')->where('member_id', $member_id)->first();

    //     // Find out member details
    //     if ($m_type == 'S') {
    //         $query = DB::table('issue_return as a')
    //             ->select(
    //                 'a.*',
    //                 'b.copy_id',
    //                 'b.status',
    //                 'd.first_name',
    //                 'd.roll_no',
    //                 'd.mid_name',
    //                 'd.last_name',
    //                 'd.class_id',
    //                 'd.section_id',
    //                 'd.academic_yr',
    //                 'd.reg_no'
    //             )
    //             ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
    //             ->join('student as d', 'a.member_id', '=', 'd.student_id');

    //         $query
    //             ->where('a.member_id', $member->member_id)
    //             ->where('a.member_type', $member->member_type)
    //             ->where('a.return_date', '0000-00-00')
    //             ->where('b.status', 'I')
    //             ->where('d.academic_yr', $acd_yr);

    //         $data = $query->first();
    //     } else if ($m_type == 'T') {
    //         $data = DB::table('issue_return as a')
    //             ->select(
    //                 'a.*',
    //                 'b.copy_id',
    //                 'b.status',
    //                 'e.name'
    //             )
    //             ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
    //             ->join('teacher as e', 'a.member_id', '=', 'e.teacher_id')
    //             ->where('a.member_type', $member->member_type)
    //             ->where('a.member_id', $member->member_id)
    //             ->where('a.return_date', '0000-00-00')
    //             ->where('b.status', 'I')
    //             ->first();
    //     }

    //     // Find out Book details
    //     $query = DB::table('issue_return')
    //         ->select(
    //             'issue_return.*',
    //             'book.book_title',
    //         );

    //     $query->leftJoin('book', 'book.book_id', '=', 'issue_return.book_id');

    //     if (!empty($member_id)) {
    //         $query->where('issue_return.member_id', $member_id);
    //     }
    //     if (!empty($m_type)) {
    //         $query->where('issue_return.member_type', $m_type);
    //     }
    //     $query->where('issue_return.return_date', '0000-00-00');
    //     $bookDetails = $query->get();
    //     return [
    //         'member' => $data,
    //         'book' => $bookDetails,
    //     ];
    // }

    private function getMemberDetailsUsingSearch($m_type, $member_id, $acd_yr)
    {
        $data = null;

        // Find out member details
        if ($m_type == 'S') {
            $query = DB::table('issue_return as a')
                ->select(
                    'a.*',
                    'b.copy_id',
                    'b.status',
                    'd.first_name',
                    'd.roll_no',
                    'd.mid_name',
                    'd.last_name',
                    'd.class_id',
                    'd.section_id',
                    'd.academic_yr',
                    'd.reg_no'
                )
                ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
                ->join('student as d', 'a.member_id', '=', 'd.student_id')
                ->where('a.member_id', $member_id)
                ->where('a.member_type', $m_type)
                ->where('a.return_date', '0000-00-00')
                ->where('b.status', 'I');

            // keep academic year check only if provided
            if (!empty($acd_yr)) {
                $query->where('d.academic_yr', $acd_yr);
            }

            $data = $query->first();
        } else if ($m_type == 'T') {
            $data = DB::table('issue_return as a')
                ->select(
                    'a.*',
                    'b.copy_id',
                    'b.status',
                    'e.name'
                )
                ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
                ->join('teacher as e', 'a.member_id', '=', 'e.teacher_id')
                ->where('a.member_type', $m_type)
                ->where('a.member_id', $member_id)
                ->where('a.return_date', '0000-00-00')
                ->where('b.status', 'I')
                ->first();
        }

        // Find out Book details
        $query = DB::table('issue_return')
            ->select(
                'issue_return.*',
                'book.book_title',
            )
            ->leftJoin('book', 'book.book_id', '=', 'issue_return.book_id')
            ->where('issue_return.member_id', $member_id)
            ->where('issue_return.member_type', $m_type)
            ->where('issue_return.return_date', '0000-00-00');

        $bookDetails = $query->get();

        return [
            'member' => $data,
            'book' => $bookDetails,
        ];
    }

    private function getMemberDetailsUsingGrn($grn_no)
    {
        $member_id = DB::table('issue_return as a')
            ->join('student as b', 'a.member_id', '=', 'b.student_id')
            ->join('book_copies as d', 'a.copy_id', '=', 'd.copy_id')
            ->where('b.reg_no', $grn_no)
            ->where('a.return_date', '0000-00-00')
            ->value('a.member_id');

        $query = DB::table('issue_return as a')
            ->select(
                'a.*',
                'b.copy_id',
                'b.status',
                'd.first_name',
                'd.roll_no',
                'd.mid_name',
                'd.last_name',
                'd.class_id',
                'd.section_id',
                'd.academic_yr',
                'd.reg_no'
            )
            ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
            ->join('student as d', 'a.member_id', '=', 'd.student_id')
            ->where('d.reg_no', $grn_no)
            ->where('a.member_id', $member_id);
        $data = $query->first();
        // Find out Book details
        $query = DB::table('issue_return')
            ->select(
                'issue_return.*',
                'book.book_title',
            );
        $query->leftJoin('book', 'book.book_id', '=', 'issue_return.book_id');
        if (!empty($member_id)) {
            $query->where('issue_return.member_id', $member_id);
        }
        $query->where('issue_return.return_date', '0000-00-00');
        $bookDetails = $query->get();
        return [
            'member' => $data,
            'book' => $bookDetails,
        ];
    }

    // issue_book_details
    public function returnBookDetails(Request $request)
    {
        $user = $this->authenticateUser();
        $acd_yr = JWTAuth::getPayload()->get('academic_year');

        $copy_id = $request->query('copy_id');

        $m_type = $request->query('m_type');
        $class_id = $request->query('class_id');
        $section_id = $request->query('section_id');
        $member_id = $request->query('member_id');

        $grn_no = $request->query('grn_no');

        $con1 = $copy_id != '';
        $con2 = $m_type != '' && $member_id != '';
        $con3 = $grn_no != '';

        if (!$con1 && !$con2 && !$con3) {
            return response()->json([
                'message' => 'Invalid inputs given to API'
            ], 403);
        }

        $memberDetails = null;

        if ($con1 && !$con2 && !$con3) {
            $memberDetails = $this->getMemberDetails('', $acd_yr, $copy_id, '');
            if (!$memberDetails) {
                return response()->json([
                    'status' => false,
                    'message' => 'The book is not issued!!!',
                ], 404);
            }
        } else if (!$con1 && $con2 && !$con3) {
            $memberDetails = $this->getMemberDetailsUsingSearch(
                $m_type,
                $member_id,
                $acd_yr,
            );
        } else if (!$con1 && !$con2 && $con3) {
            $memberDetails = $this->getMemberDetailsUsingGrn($grn_no);
            if ($memberDetails['member'] == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'The book is not issued!!!',
                ], 404);
            }
        }

        return response()->json([
            'data' => $memberDetails,
        ]);
    }

    // Old
    // public function returnOrReissue(Request $request)
    // {
    //     // 1) Validate input
    //     $validator = Validator::make($request->all(), [
    //         'operation' => 'required|in:return,reissue',
    //         'selector' => 'required|array|min:1',
    //         'selector.*' => 'required',  // copy_id values
    //         'book_id' => 'nullable|array',
    //         'book_id.*' => 'nullable',
    //         'member_id' => 'required|integer',
    //         'member_type' => 'required|in:S,T',
    //         'dateofreturn' => 'required|date',  // expecting a date string
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $operation = $request->input('operation');  // 'return' or 'reissue'
    //     $copyIds = $request->input('selector');  // array
    //     $bookIds = $request->input('book_id', []);  // array (may be empty for return)
    //     $memberId = $request->input('member_id');
    //     $memberType = $request->input('member_type');  // 'S' or 'T'
    //     $dateOfReturnRaw = $request->input('dateofreturn');

    //     // Normalize date using Carbon (server timezone)
    //     try {
    //         $returnDate = Carbon::parse($dateOfReturnRaw)->toDateString();  // 'Y-m-d'
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid dateofreturn format'
    //         ], 422);
    //     }

    //     // Use transactions to keep DB consistent
    //     DB::beginTransaction();

    //     try {
    //         $processed = [];
    //         foreach ($copyIds as $i => $copyId) {
    //             // Ensure we are working with trimmed values
    //             $copyId = trim($copyId);

    //             // 1) Ensure there is an active issue record for this copy (return_date = '0000-00-00')
    //             $issueQuery = DB::table('issue_return')
    //                 ->where('copy_id', $copyId)
    //                 ->where('return_date', '0000-00-00');

    //             // If the action is return or reissue we also verify member & member_type matches if given
    //             if (!empty($memberId)) {
    //                 $issueQuery->where('member_id', $memberId);
    //                 $issueQuery->where('member_type', $memberType);
    //             }

    //             $activeIssue = $issueQuery->first();

    //             if (!$activeIssue) {
    //                 // no active issue found — record and continue (or you can choose to abort)
    //                 $processed[] = [
    //                     'copy_id' => $copyId,
    //                     'status' => 'not_found_or_already_returned'
    //                 ];
    //                 // choose to continue with other copy_ids rather than aborting all
    //                 continue;
    //             }

    //             // 2) Update existing issue_return row's return_date (mark as returned)
    //             $updated = DB::table('issue_return')
    //                 ->where('copy_id', $copyId)
    //                 ->where('return_date', '0000-00-00')
    //                 ->update(['return_date' => $returnDate]);

    //             // 3) Update book_copies.status = 'A' (Available) for a plain return
    //             if ($operation === 'return') {
    //                 DB::table('book_copies')
    //                     ->where('copy_id', $copyId)
    //                     ->update(['status' => 'A']);

    //                 $processed[] = [
    //                     'copy_id' => $copyId,
    //                     'status' => $updated ? 'returned' : 'update_failed'
    //                 ];
    //             }

    //             // 4) For reissue: after marking old record returned, insert a new issue_return row
    //             if ($operation === 'reissue') {
    //                 // prepare fields for new issue_return row
    //                 $newIssue = [
    //                     'book_id' => isset($bookIds[$i]) ? $bookIds[$i] : ($activeIssue->book_id ?? null),
    //                     'copy_id' => $copyId,
    //                     'member_id' => $memberId,
    //                     'member_type' => $memberType,
    //                     // using return date as new issue_date (same as original CI behavior)
    //                     'issue_date' => $returnDate,
    //                 ];

    //                 // due_date depends on member type: students 7 days, others 30 days
    //                 if ($memberType === 'S') {
    //                     $due = Carbon::parse($returnDate)->addDays(7);
    //                 } else {
    //                     $due = Carbon::parse($returnDate)->addDays(30);
    //                 }
    //                 $newIssue['due_date'] = $due->toDateTimeString();

    //                 // Insert new issue row
    //                 DB::table('issue_return')->insert($newIssue);

    //                 // Ensure book_copies.status => 'I' (Issued)
    //                 DB::table('book_copies')
    //                     ->where('copy_id', $copyId)
    //                     ->update(['status' => 'I']);

    //                 $processed[] = [
    //                     'copy_id' => $copyId,
    //                     'status' => 'reissued'
    //                 ];
    //             }
    //         }  // end foreach

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => $operation === 'return' ? 'Book(s) Returned' : 'Book(s) Reissued',
    //             'details' => $processed
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         // log exception in real app
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Database error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function returnBook(Request $request)
    {
        DB::beginTransaction();

        try {
            // ---------- Inputs ----------
            $copyIds = $request->input('selector', []);  // array of copy_ids
            $bookId = $request->input('book_id');
            $memberId = $request->input('member_id');
            $memberType = $request->input('member_type');
            $returnDate = Carbon::parse($request->input('dateofreturn'))->format('Y-m-d');

            // ---------- Data to update ----------
            $issueReturnData = [
                'return_date' => $returnDate
            ];

            $bookCopyData = [
                'status' => 'A'
            ];

            // ---------- Loop through copies ----------
            foreach ($copyIds as $copyId) {
                // Update issue_return table
                DB::table('issue_return')
                    ->where('copy_id', $copyId)
                    ->where('member_id', $memberId)
                    ->where('member_type', $memberType)
                    ->where('return_date', '0000-00-00')
                    ->update($issueReturnData);

                // Update book_copies table
                DB::table('book_copies')
                    ->where('copy_id', $copyId)
                    ->update($bookCopyData);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Book(s) returned successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to return book(s)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reissueBook(Request $request)
    {
        DB::beginTransaction();

        try {
            // ---------- Inputs ----------
            $copyIds = $request->input('selector', []);  // array
            $bookIds = $request->input('book_id', []);  // array (index-mapped)
            $memberId = $request->input('member_id');
            $memberType = $request->input('member_type');

            $returnDate = Carbon::parse($request->input('dateofreturn'))->format('Y-m-d');

            // ---------- Loop through copies ----------
            foreach ($copyIds as $index => $copyId) {
                // 1️⃣ Mark previous issue as returned
                DB::table('issue_return')
                    ->where('copy_id', $copyId)
                    ->where('member_id', $memberId)
                    ->where('member_type', $memberType)
                    ->where('return_date', '0000-00-00')
                    ->update([
                        'return_date' => $returnDate
                    ]);

                // 2️⃣ Calculate new issue & due dates
                $issueDate = Carbon::parse($returnDate);

                if ($memberType === 'S') {
                    $dueDate = $issueDate->copy()->addDays(7);
                } else {
                    $dueDate = $issueDate->copy()->addDays(30);
                }

                // 3️⃣ Insert new issue record (re-issue)
                DB::table('issue_return')->insert([
                    'book_id' => $bookIds[$index] ?? null,
                    'copy_id' => $copyId,
                    'member_id' => $memberId,
                    'member_type' => $memberType,
                    'issue_date' => $issueDate->format('Y-m-d'),
                    'due_date' => $dueDate->format('Y-m-d H:i:s'),
                    'return_date' => '0000-00-00'
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Book(s) reissued successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to reissue book(s)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMemberOnAccession($copy_id)
    {
        // returns single member_id holding the copy (not yet returned)
        $row = DB::table('issue_return')
            ->select('member_id')
            ->where('copy_id', $copy_id)
            ->where('return_date', '0000-00-00')
            ->first();

        if (!$row) {
            return response()->json(null, 204);  // No Content
        }

        return response()->json(['member_id' => $row->member_id], 200);
    }

    // GET /api/issue/member-on-grno/{reg_no}
    public function getMemberOnGrno($reg_no)
    {
        // join student and issue_return to find the member_id for this reg_no
        $row = DB::table('issue_return as a')
            ->join('student as b', 'a.member_id', '=', 'b.student_id')
            ->select('a.member_id')
            ->where('b.reg_no', $reg_no)
            ->where('a.return_date', '0000-00-00')
            ->first();

        if (!$row) {
            return response()->json(null, 204);
        }

        return response()->json(['member_id' => $row->member_id], 200);
    }

    // GET /api/issue/records?m_type=S&member_id=12
    public function getIssueReturn(Request $request)
    {
        $m_type = $request->query('m_type');  // optional
        $member_id = $request->query('member_id');  // optional

        $q = DB::table('issue_return')->select('*');

        if (!empty($member_id)) {
            $q->where('issue_return.member_id', $member_id);
        }
        if (!empty($m_type)) {
            $q->where('issue_return.member_type', $m_type);
        }

        // only active (not returned)
        $q->where('issue_return.return_date', '0000-00-00');

        $rows = $q->get();

        if ($rows->isEmpty()) {
            return response()->json([], 200);
        }

        return response()->json($rows, 200);
    }

    // GET /api/issue/student-data?copy_id=CPY32&acd_yr=2024&grn_no=
    public function getMemDataTypeStudent(Request $request)
    {
        $copy_id = $request->query('copy_id');
        $acd_yr = $request->query('acd_yr');
        $grn_no = $request->query('grn_no');

        $q = DB::table('issue_return as a')
            ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
            ->join('student as d', 'a.member_id', '=', 'd.student_id')
            ->select(
                'a.*',
                'b.copy_id as copy_id',
                'b.status as copy_status',
                'd.first_name',
                'd.mid_name',
                'd.last_name',
                'd.roll_no',
                'd.class_id',
                'd.section_id',
                'd.academic_yr',
                'd.reg_no'
            );

        if (!empty($grn_no)) {
            // search by registration number (GR no)
            $q->where('d.reg_no', $grn_no);
        } else {
            // search by accession no + active issue + matching academic year + copy status
            $q
                ->where('a.copy_id', $copy_id)
                ->where('a.return_date', '0000-00-00')
                ->where('b.status', 'I');

            if (!empty($acd_yr)) {
                $q->where('d.academic_yr', $acd_yr);
            }
        }

        $rows = $q->get();

        return response()->json($rows, 200);
    }

    // GET /api/issue/staff-data?copy_id=CPY32
    public function getMemDataTypeStaff(Request $request)
    {
        $copy_id = $request->query('copy_id');

        $rows = DB::table('issue_return as a')
            ->join('book_copies as b', 'a.copy_id', '=', 'b.copy_id')
            ->join('teacher as e', 'a.member_id', '=', 'e.teacher_id')
            ->select(
                'a.*',
                'b.copy_id as copy_id',
                'b.status as copy_status',
                'e.name as teacher_name'
            )
            ->where('a.copy_id', $copy_id)
            ->where('a.return_date', '0000-00-00')
            ->where('b.status', 'I')
            ->get();

        return response()->json($rows, 200);
    }

    public function checkForAccessionNo(Request $request)
    {
        $user = $this->authenticateUser();
        $accession_no = $request->query('accesion_no');  // keep spelling as-is

        if (!$accession_no) {
            return response()->json([
                'status' => false,
                'message' => 'Accession number is required'
            ], 400);
        }

        $exists = DB::table('book_copies')
            ->where('copy_id', $accession_no)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => true,
                'available' => false,
                'message' => 'Accession number already exists'
            ]);
        }

        return response()->json([
            'status' => true,
            'available' => true,
            'message' => 'Accession number is available'
        ]);
    }

    public function searchReminderRemark(Request $request)
    {
        DB::beginTransaction();

        try {
            // Optional params
            $class_id = $request->query('class_id');
            $section_id = $request->query('section_id');
            $date = $request->query('date');  // expected: YYYY-MM-DD

            $today = Carbon::today()->toDateString();

            // =========================
            // BASE QUERY (COMMON PART)
            // =========================
            $baseQuery = DB::table('issue_return as a')
                ->join('book', 'a.book_id', '=', 'book.book_id')
                ->join('student', 'a.member_id', '=', 'student.student_id')
                ->join('class as b', 'student.class_id', '=', 'b.class_id')
                ->join('section as c', 'student.section_id', '=', 'c.section_id')
                ->select(
                    'a.*',
                    'b.name as class_name',
                    'c.name as section_name',
                    'book.book_title',
                    'student.first_name',
                    'student.mid_name',
                    'student.last_name',
                    'student.class_id',
                    'student.section_id'
                )
                ->where('a.member_type', 'S')
                ->where('a.return_date', '0000-00-00');

            // =========================
            // DATE CONDITION (FIXED)
            // =========================
            if (!empty($date)) {
                // exact date match (CI: due_date = $date)
                $baseQuery->whereDate('a.due_date', $date);
            } else {
                // overdue till today (CI: due_date <= today)
                $baseQuery->whereDate('a.due_date', '<=', $today);
            }

            // =========================
            // CLASS / SECTION CONDITION
            // =========================
            if (!empty($class_id)) {
                $baseQuery->where('student.class_id', $class_id);
            }

            if (!empty($section_id)) {
                $baseQuery->where('student.section_id', $section_id);
            }

            // =========================
            // NOT IN REMARK LOG
            // =========================
            $notInQuery = (clone $baseQuery)->whereRaw(
                'CONCAT(student.student_id, book.book_id, a.due_date) NOT IN (
                    SELECT CONCAT(student_id, book_id, due_date)
                    FROM nonreturned_books_remark_log
                )'
            );

            // =========================
            // IN REMARK LOG
            // =========================
            $inQuery = (clone $baseQuery)->whereRaw(
                'CONCAT(student.student_id, book.book_id, a.due_date) IN (
                    SELECT CONCAT(student_id, book_id, due_date)
                    FROM nonreturned_books_remark_log
                )'
            );

            // =========================
            // UNION BOTH
            // =========================
            $results = $notInQuery
                ->union($inQuery)
                ->get();

            // =========================
            // REMARK COUNT
            // =========================
            foreach ($results as $result) {
                $result->count = DB::table('nonreturned_books_remark_log')
                    ->where('student_id', $result->member_id)
                    ->where('book_id', $result->book_id)
                    ->where('due_date', $result->due_date)
                    ->count();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'count' => $results->count(),
                'data' => $results
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reminder remarks',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // public function searchReminderRemark(Request $request)
    // {
    //     try {
    //         // Optional params
    //         $class_id   = $request->query('class_id');
    //         $section_id = $request->query('section_id');
    //         $date       = $request->query('date'); // Y / N

    //         $today = Carbon::today()->toDateString();

    //         $query = DB::table('issue_return as a')
    //             ->join('book', 'a.book_id', '=', 'book.book_id')
    //             ->join('student', 'a.member_id', '=', 'student.student_id')
    //             ->join('class as b', 'student.class_id', '=', 'b.class_id')
    //             ->join('section as c', 'student.section_id', '=', 'c.section_id')
    //             ->leftJoin('nonreturned_books_remark_log as nr', function ($join) {
    //                 $join->on('nr.student_id', '=', 'student.student_id')
    //                     ->on('nr.book_id', '=', 'a.book_id')
    //                     ->on('nr.due_date', '=', 'a.due_date');
    //             })
    //             ->select(
    //                 'a.*',
    //                 'b.name as class_name',
    //                 'c.name as section_name',
    //                 'book.book_title',
    //                 'student.first_name',
    //                 'student.mid_name',
    //                 'student.last_name',
    //                 'student.class_id',
    //                 'student.section_id',
    //                 DB::raw('COUNT(nr.id) as remark_count')
    //             )
    //             ->where('a.member_type', 'S')
    //             ->where('a.return_date', '0000-00-00');

    //         // =========================
    //         // DATE CONDITION
    //         // =========================
    //         if (!empty($date)) {
    //             $query->where('a.due_date', '<=', $today);
    //         }

    //         // =========================
    //         // CLASS / SECTION CONDITION
    //         // =========================
    //         if (!empty($class_id)) {
    //             $query->where('student.class_id', $class_id);
    //         }

    //         if (!empty($section_id)) {
    //             $query->where('student.section_id', $section_id);
    //         }

    //         // =========================
    //         // GROUP BY (IMPORTANT)
    //         // =========================
    //         $query->groupBy(
    //             'a.issue_return_id',
    //             'b.name',
    //             'c.name',
    //             'book.book_title',
    //             'student.first_name',
    //             'student.mid_name',
    //             'student.last_name',
    //             'student.class_id',
    //             'student.section_id'
    //         );

    //         $result = $query->get();

    //         return response()->json([
    //             'status' => true,
    //             'count'  => $result->count(),
    //             'data'   => $result
    //         ], 200);

    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Failed to fetch reminder remarks',
    //             'error'   => config('app.debug') ? $e->getMessage() : null
    //         ], 500);
    //     }
    // }

    public function sendReminderRemark(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $reg_id = $user->reg_id;
            $academic_year = JWTAuth::getPayload()->get('academic_year');
            $settingsData = getSchoolSettingsData();
            $schoolName = $settingsData->institute_name;
            $defaultPassword = $settingsData->default_pwd;
            $websiteUrl = $settingsData->website_url;
            $shortName = $settingsData->short_name;
            $whatsappIntegration = $settingsData->whatsapp_integration;
            $smsIntegration = $settingsData->sms_integration;
            $savepublish = 'Y';

            $remark_type = 'Remark';
            $kvalue = $request->input('kvalue');

            for ($i = 1; $i < $kvalue; $i++) {
                $student_id = $request->input('checkbox' . $i);
                if (isset($student_id)) {
                    $class_id = $request->input('class_id' . $i);
                    $section_id = $request->input('section_id' . $i);
                    $teacher_id = $reg_id;
                    $academic_yr = $academic_year;
                    $remark_desc = $request->input('remark_desc' . $i);
                    $remark_subject = $request->input('remark_subject' . $i);
                    $publish = 'N';
                    $publish_date = date('Y-m-d');  // 05-09-19
                    $acknowledge = 'N';
                    $student_id = $student_id;

                    // NOTIFICATION STUFF - START

                    $insertData = [
                        'remark_type' => $remark_type,
                        'remark_desc' => $remark_desc,
                        'remark_subject' => $remark_subject,
                        'class_id' => $class_id,
                        'section_id' => $section_id,
                        'subject_id' => '',
                        'teacher_id' => $user->reg_id,
                        'academic_yr' => $academic_year,
                        'remark_date' => \Carbon\Carbon::parse($request->input('remark_date'))->format('Y-m-d'),
                        'publish_date' => \Carbon\Carbon::today()->toDateString(),
                        'publish' => 'Y',
                        'acknowledge' => 'N',
                        'student_id' => $student_id,
                    ];

                    $remarkId = DB::table('remark')->insertGetId($insertData);

                    // Job call stuff.
                    // 2. Dispatch async job
                    SendReminderRemarkJob::dispatch(
                        $student_id,
                        [
                            'remark_desc' => $remark_desc,
                            'remark_subject' => $remark_subject,
                            'academic_year' => $academic_year,
                            'teacher_id' => $reg_id,
                        ],
                        $remarkId
                    );

                    // Rest of the part
                    $book_id = $request->input('book_id' . $i);
                    $due_date = $request->input('due_date' . $i);
                    DB::table('nonreturned_books_remark_log')
                        ->insert([
                            'remark_id' => $remarkId,
                            'book_id' => $book_id,
                            'student_id' => $student_id,
                            'due_date' => $due_date,
                        ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Remark Sent!!!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reminder remarks',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    // ##############################
    // Library Module
    // ##############################
    // ------------------------------------

    // ============================
    // Periodicals - Menu
    // ============================
    // Developer - Leo The Great - 2026-01-23
    // ----------------------------------------------
    /** Periodicals - Tab - START */

    /**
     * Listing of Periodicals
     * GET /library/periodicals
     */
    public function periodicalsIndex(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            $data = DB::table('periodicals')
                ->orderby('periodicals.periodical_id', 'desc')  // defaults to created_at
                ->get();

            return response()->json([
                'status' => true,
                'data' => $data,
                'count' => count($data),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch periodicals',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Create Periodical
     * POST /library/periodicals
     */
    public function storePeriodical(Request $request)
    {
        try {
            $this->authenticateUser();

            $title = $request->input('title');
            $subscription_no = $request->input('subscription_no');
            $frequency = $request->input('frequency');
            $email_ids = $request->input('email_ids');

            if (!$title || !$subscription_no || !$frequency) {
                return response()->json([
                    'status' => false,
                    'Message' => 'title, subscription_no, frequency are required '
                ], 400);
            }

            $data = DB::table('periodicals')->insert([
                'title' => $title,
                'subscription_no' => $subscription_no,
                'frequency' => $frequency,
                'email_ids' => $email_ids ?? '',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Periodical Created',
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to store periodical, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update Periodical
     * PUT /library/periodicals/{id}
     */
    public function updatePeriodical(Request $request, $id)
    {
        try {
            $this->authenticateUser();

            $title = $request->input('title');
            $subscription_no = $request->input('subscription_no');
            $frequency = $request->input('frequency');
            $email_ids = $request->input('email_ids');

            if (!$title || !$subscription_no || !$frequency) {
                return response()->json([
                    'status' => false,
                    'message' => 'title, subscription_no, frequency are required'
                ], 403);
            }

            $updated = DB::table('periodicals')
                ->where('periodical_id', $id)
                ->update([
                    'title' => $title,
                    'subscription_no' => $subscription_no,
                    'frequency' => $frequency,
                    'email_ids' => $email_ids ?? ''
                ]);

            if (!$updated) {
                return response()->json([
                    'status' => false,
                    'message' => 'Periodical not found or no changes made'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Periodical Updated Successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update periodical, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete Periodical
     * DELETE /library/periodicals/{id}
     */
    public function deletePeriodical($id)
    {
        try {
            $this->authenticateUser();

            // Check if a subscription exists for this periodical. If yes, do not allow deletion.
            if (DB::table('subscription')->where('periodical_id', $id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'The periodical cannot be deleted because a subscription already exists for it.',
                ], 409);
            }

            $deleted = DB::table('periodicals')
                ->where('periodical_id', $id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'status' => false,
                    'message' => 'Periodical not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Periodical Deleted Successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete periodical, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /** Periodicals - Tab - END */
    /** Subscription - Tab - START */

    /**
     * GET /library/subscriptions
     */
    public function subscriptionIndex(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            $data = DB::table('subscription')
                ->leftJoin('periodicals', 'periodicals.periodical_id', '=', 'subscription.periodical_id')
                ->orderby('subscription.subscription_id', 'DESC')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $data,
                'count' => count($data),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch Subscription Details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST /library/subscriptions
     */
    public function subscriptionCreate(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;

            if ($role != 'L' && $role != 'U') {
                return response()->json([
                    'message' => 'You are not allowed to access this resource.'
                ], 401);
            }

            $periodical_id = $request->input('periodical_id');
            $oldSubscriptions = DB::table('subscription')->where('periodical_id', $periodical_id)->get();
            $from_date = date('Y-m-d', strtotime($request->from_date));
            $to_date = date('Y-m-d', strtotime($request->to_date));
            $receiving_date = $request->input('receiving_date');
            $bimonthly_second_date = $request->input('bimonthly_second_date');
            $status = 'Active';

            $periodical = DB::table('periodicals')->where('periodical_id', $periodical_id)->first();

            $frequency = $periodical->frequency;

            if ($frequency == 'Bimonthly' && !$bimonthly_second_date) {
                return response()->json([
                    'status' => false,
                    'message' => 'bimonthly_second_date is required for Bimonthly frequency'
                ], 400);
            }

            if (!$periodical_id || !$from_date || !$to_date || !$receiving_date) {
                return response()->json([
                    'status' => false,
                    'Message' => 'periodical_id, from_date, to_date, receiving_date are required'
                ], 400);
            }

            if (count($oldSubscriptions) > 0) {
                DB::table('subscription')->where('periodical_id', $periodical_id)->update(['status' => 'Expired']);
            }

            $data = DB::table('subscription')->insert([
                'periodical_id' => $periodical_id,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'status' => $status,
                'receiving_date' => $receiving_date,
                'bimonthly_second_date' => $bimonthly_second_date,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Subscription Created Successfully',
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to store subscription details, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * PUT /library/subscriptions/{subscription_id}
     */
    public function subscriptionUpdate(Request $request, $subscription_id)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;

            if ($role != 'L' && $role != 'U') {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to access this resource.'
                ], 401);
            }

            // Check subscription exists
            $subscription = DB::table('subscription')
                ->where('subscription_id', $subscription_id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            // Allowed editable fields ONLY
            $from_date = $request->input('from_date') ? date('Y-m-d', strtotime($request->input('from_date'))) : $subscription->from_date;
            $to_date = $request->input('to_date') ? date('Y-m-d', strtotime($request->input('to_date'))) : $subscription->to_date;
            $receiving_date = $request->input('receiving_date') ?? $subscription->receiving_date;
            $bimonthly_second_date = $request->input('bimonthly_second_date');
            $status = $request->input('status') ?? $subscription->status;

            $periodical = DB::table('periodicals')->where('periodical_id', $subscription->periodical_id)->first();

            $frequency = $periodical->frequency;

            if ($frequency == 'Bimonthly' && !$bimonthly_second_date) {
                return response()->json([
                    'status' => false,
                    'message' => 'bimonthly_second_date is required for Bimonthly frequency'
                ], 400);
            }

            $updated = DB::table('subscription')
                ->where('subscription_id', $subscription_id)
                ->update([
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                    'receiving_date' => $receiving_date,
                    'bimonthly_second_date' => $bimonthly_second_date,
                    'status' => $status,
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Subscription Updated Successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update subscription details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * DELETE /library/subscriptions/{subscription_id}
     */
    public function subscriptionDelete($subscription_id)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;

            if ($role != 'L' && $role != 'U') {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to access this resource.'
                ], 401);
            }

            // 1️⃣ Check if volumes/issues exist with date_received != '0000-00-00'
            $issues = DB::table('subscription as a')
                ->join('subscription_volume as c', 'a.subscription_id', '=', 'c.subscription_id')
                ->join('subscription_issues as b', 'c.subscription_vol_id', '=', 'b.subscription_vol_id')
                ->where('a.subscription_id', $subscription_id)
                ->where('b.date_received', '!=', '0000-00-00')
                ->get();

            if ($issues->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription details cannot be deleted because the status is expired.'
                ], 400);
            }

            // 2️⃣ Get subscription_vol_id
            $subscriptionVol = DB::table('subscription_volume')
                ->where('subscription_id', $subscription_id)
                ->first();

            if ($subscriptionVol) {
                // 3️⃣ Delete subscription_issues
                DB::table('subscription_issues')
                    ->where('subscription_vol_id', $subscriptionVol->subscription_vol_id)
                    ->delete();

                // 4️⃣ Delete subscription_volume
                DB::table('subscription_volume')
                    ->where('subscription_vol_id', $subscriptionVol->subscription_vol_id)
                    ->delete();
            }

            // 5️⃣ Delete subscription
            DB::table('subscription')
                ->where('subscription_id', $subscription_id)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Subscription Details deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete subscription',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * GET /library/subscriptions/{subscription_id}/volumes
     */
    public function subscriptionVolumeIndex($subscription_id)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;
            $academic_year = JWTAuth::getPayload()->get('academic_year');

            if ($role != 'L' && $role != 'U') {
                return response()->json([
                    'message' => 'You are not allowed to access this resource.'
                ], 401);
            }

            $data = DB::table('subscription_volume')->where('subscription_id', $subscription_id)->get();

            return response()->json([
                'status' => true,
                'data' => $data,
                'count' => count($data),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch Subscription Volume Details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST /library/subscriptions/{subscription_id}/volumes
     */
    public function subscriptionVolumeStore(Request $request, $subscription_id)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;

            if ($role !== 'L' && $role !== 'U') {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to access this resource.'
                ], 401);
            }

            // Inputs
            $subscription_id = $subscription_id;
            $volume_start_dates = $request->input('volume_start_dates');

            $subscription_to_date = $request->input('subscription_to_date');
            $receiving_date = $request->input('receiving_date');
            // $bimonthly_second_date = $request->input('bimonthly_second_date');
            $frequency = $request->input('frequency');
            $volume_lists = $request->input('volume');
            $issue_lists = $request->input('issue');

            // if ($frequency === 'Bimonthly' && !$bimonthly_second_date) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'bimonthly_second_date is required for Bimonthly frequency'
            //     ], 400);
            // }

            $subscription = DB::table('subscription')->where('subscription_id', $subscription_id)
                ->first();

            $bimonthly_second_date = $subscription->bimonthly_second_date;

            if (
                !$subscription_id ||
                !$volume_start_dates ||
                !$subscription_to_date ||
                !$receiving_date ||
                !$frequency ||
                !$volume_lists ||
                !$issue_lists
            ) {
                return response()->json([
                    'status' => false,
                    'Message' => 'subscription_id, volume_start_dates, subscription_to_date, receiving_date, frequency, volume, issue are required'
                ], 400);
            }

            DB::beginTransaction();

            for ($i = 0; $i < count($volume_lists); $i++) {
                $volume_start_date = date('Y-m-d', strtotime($volume_start_dates[$i]));

                $from_year = date('Y', strtotime($volume_start_date));
                $from_month = date('m', strtotime($volume_start_date));

                // Initial receive_by_date
                if ($frequency === 'Weekly') {
                    $received_by_date = date(
                        'Y-m-d',
                        strtotime($receiving_date, strtotime($volume_start_date))
                    );
                } else {
                    $received_by_date = $from_year . '-' . $from_month . '-' . $receiving_date;
                }

                // Insert subscription_volume
                $subscriptionVolId = DB::table('subscription_volume')->insertGetId([
                    'subscription_id' => $subscription_id,
                    'volume_start_date' => $volume_start_date,
                    'volume' => $volume_lists[$i],
                    'no_of_issues' => $issue_lists[$i],
                ]);

                $no_of_issue_count = $issue_lists[$i];

                for ($j = 1; $j <= $no_of_issue_count; $j++) {
                    if ($j != 1) {
                        if ($frequency === 'Monthly') {
                            $received_by_date = date(
                                'Y-m-d',
                                strtotime($received_by_date . ' +1 month')
                            );
                        }

                        // if ($frequency === 'Bimonthly') {
                        //     $received_by_date = date(
                        //         'Y-m-d',
                        //         strtotime('+15 day', strtotime($received_by_date))
                        //     );

                        //     if ($j % 2 != 0) {
                        //         $month = date('m', strtotime($received_by_date));
                        //         $year = date('Y', strtotime($received_by_date));
                        //         $received_by_date = $year . '-' . $month . '-' . $receiving_date;
                        //     }
                        // }
                        if ($frequency === 'Bimonthly') {

                            $month = date('m', strtotime($received_by_date));
                            $year  = date('Y', strtotime($received_by_date));

                            if ($j % 2 == 0) {
                                $received_by_date = date(
                                    'Y-m-d',
                                    strtotime($year . '-' . $month . '-' . str_pad($bimonthly_second_date, 2, '0', STR_PAD_LEFT))
                                );
                            } else {
                                $received_by_date = date(
                                    'Y-m-d',
                                    strtotime($year . '-' . $month . '-' . str_pad($receiving_date, 2, '0', STR_PAD_LEFT) . ' +1 month')
                                );
                            }
                        }
                        if ($frequency === 'Weekly') {
                            $received_by_date = date(
                                'Y-m-d',
                                strtotime('+7 day', strtotime($received_by_date))
                            );
                        }
                    }

                    // Insert subscription_issues
                    DB::table('subscription_issues')->insert([
                        'subscription_vol_id' => $subscriptionVolId,
                        'issue' => $j,
                        'receive_by_date' => $received_by_date,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Volume Created Successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to store Volume, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * DELETE /library/subscriptions/volumes/{subscription_vol_id}
     */
    public function subscriptionVolumeDelete(Request $request, $subscription_vol_id)
    {
        try {
            $user = $this->authenticateUser();
            DB::table('subscription_volume')->where('subscription_vol_id', $subscription_vol_id)->delete();
            DB::table('subscription_issues')->where('subscription_vol_id', $subscription_vol_id)->delete();
            return response()->json([
                'status' => true,
                'message' => 'Volume deleted.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete volume',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /** Subscription - Tab - END */
    /** Change Periodical Status - START */

    /**
     * GET  /library/get_volumes_by_periodical_id/{id}
     */
    public function getVolumesByPeriodicalId($id)
    {
        try {
            $user = $this->authenticateUser();

            $volumes = DB::table('subscription_volume as sv')
                ->join('subscription as s', 'sv.subscription_id', '=', 's.subscription_id')
                ->join('periodicals as p', 's.periodical_id', '=', 'p.periodical_id')
                ->where('s.periodical_id', $id)
                ->select(
                    '*'
                )
                ->get();

            return response()->json([
                'status' => true,
                'data' => $volumes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch Volumes for pid: ' . $id,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * GET  /library/get_volumes_issues/{subscription_vol_id}
     */
    public function getVolumesIssues($subscription_vol_id)
    {
        try {
            $user = $this->authenticateUser();

            $issues = DB::table('subscription_issues')
                ->where('subscription_vol_id', $subscription_vol_id)
                ->get();

            $status = DB::table('subscription as a')
                ->join('subscription_volume as c', 'a.subscription_id', '=', 'c.subscription_id')
                ->join('subscription_issues as b', 'c.subscription_vol_id', '=', 'b.subscription_vol_id')
                ->where('c.subscription_vol_id', $subscription_vol_id)
                ->value('a.status');

            return response()->json([
                'status' => true,
                'data' => $issues,
                'subscription_status' => $status,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch Issues for subscription_vol_id: ' . $subscription_vol_id,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * POST /library/update_periodical_status/{subscription_vol_id}
     */
    public function updatePeriodicalStatus(Request $request, $subscription_vol_id)
    {
        try {
            $user = $this->authenticateUser();

            $issues = $request->input('issue', []);
            $dateReceived = $request->input('date_received', []);
            $receiveBy = $request->input('receive_by_date', []);

            if (!$issues || !$dateReceived || !$receiveBy) {
                return response()->json([
                    'message' => 'issues, date_received, receive_by_date is required',
                ], 403);
            }

            foreach ($issues as $i => $issue) {
                if (!$issue) {
                    continue;
                }

                // $data = [
                //     'receive_by_date' => !empty($receiveBy[$i])
                //         ? date('Y-m-d', strtotime($receiveBy[$i]))
                //         : '',
                // ];

                if (!empty($dateReceived[$i])) {
                    $data['date_received'] = date('Y-m-d', strtotime($dateReceived[$i]));
                    $data['receive_by_date'] = date('Y-m-d', strtotime($receiveBy[$i]));
                    $data['status'] = 'Received';
                } else {
                    $data['date_received'] = null;
                    $data['receive_by_date'] = date('Y-m-d', strtotime($receiveBy[$i]));
                    $data['status'] = null;
                }

                DB::table('subscription_issues')
                    ->where('subscription_vol_id', $subscription_vol_id)
                    ->where('issue', $issue)
                    ->update($data);
            }

            return response()->json([
                'status' => true,
                'message' => 'Issue Status Changed'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /** Change Periodical Status - END */
    /** Periodical Not Received Report - START */

    /**
     * /library/periodical_not_received_report
     */
    public function periodicalNotReceivedReport($periodical_id = null)
    {
        try {
            $user = $this->authenticateUser();
            $role_id = $user->role_id;

            // if ($role_id !== 'L') {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'You are not allowed to access this resource',
            //     ], 401);
            // }

            $query = DB::table('periodicals as a')
                ->join('subscription as b', 'a.periodical_id', '=', 'b.periodical_id')
                ->join('subscription_volume as c', 'b.subscription_id', '=', 'c.subscription_id')
                ->join('subscription_issues as d', 'c.subscription_vol_id', '=', 'd.subscription_vol_id')
                ->where('b.status', 'Active')
                ->where('d.receive_by_date', '<', DB::raw('CURDATE()'))
                ->where('d.status', '!=', 'Received')
                ->orderby('d.receive_by_date', 'desc');

            // apply condition only if periodical_id is passed
            if (!empty($periodical_id)) {
                $query->where('a.periodical_id', $periodical_id);
            }

            $data = $query
                ->select('a.*', 'b.*', 'c.*', 'd.*')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $data,
                'count' => count($data),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /** Periodical Not Received Report - END */
    /** Periodical Report - START */

    /**
     * GET /library/periodicals_report
     */
    public function periodicalsReport(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $role_id = $user->role_id ?? null;

            if ($role_id !== 'L') {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to access this resource',
                ], 401);
            }

            $periodical_id = $request->input('periodical_id');
            $subscription_vol_id = $request->input('subscription_vol_id');
            $subscription_issue_id = $request->input('subscription_issue_id');
            $received_date = $request->input('received_date');

            $query = DB::table('subscription_issues as a')
                ->select(
                    'a.issue',
                    'a.receive_by_date',
                    'a.date_received',
                    'b.volume',
                    'd.title',
                    'd.subscription_no'
                )
                ->join('subscription_volume as b', 'a.subscription_vol_id', '=', 'b.subscription_vol_id')
                ->join('subscription as c', 'c.subscription_id', '=', 'b.subscription_id')
                ->join('periodicals as d', 'c.periodical_id', '=', 'd.periodical_id')
                ->where('a.status', 'Received')
                ->orderby('a.receive_by_date', 'desc');

            if (!empty($periodical_id)) {
                $query->where('d.periodical_id', $periodical_id);
            }

            if (!empty($subscription_vol_id)) {
                $query->where('b.subscription_vol_id', $subscription_vol_id);
            }

            if (!empty($received_date)) {
                $query->where('a.date_received', $received_date);
            }

            if (!empty($subscription_issue_id)) {
                $query->where('a.subscription_issue_id', $subscription_issue_id);
            }

            $data = $query->get();

            return response()->json([
                'status' => true,
                'data' => $data,
                'count' => count($data),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch, Server Error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /** Periodical Report - END */
    // ----------------------------------------------

    // ============================
    // Dashboard - Menu
    // ============================
    // Developer - Leo The Great - 2026-01-28
    // ----------------------------------------------
    /** Subscription Reminder Report */

    /**
     * GET /library/subscription_reminder
     */
    public function subscriptionReminder(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;

            if ($role !== 'L') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $today = Carbon::today();

            $subscriptions = DB::table('subscription')
                ->leftJoin('periodicals', 'periodicals.periodical_id', '=', 'subscription.periodical_id')
                ->where('status', 'Active')
                ->whereDate(DB::raw('DATE_SUB(to_date, INTERVAL 7 DAY)'), '<', $today)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $subscriptions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reminder, Server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /** Subscription Reminder Report */
    /** Periodical Not Received Report */

    /**
     * GET /library/dashboard/periodical_not_received_report
     */
    public function dashboardPeriodicalNotReceivedReport(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $role = $user->role_id;

            if ($role !== 'L') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $periodical_id = $request->periodical_id;  // optional

            $query = DB::table('periodicals as a')
                ->join('subscription as b', 'a.periodical_id', '=', 'b.periodical_id')
                ->join('subscription_volume as c', 'b.subscription_id', '=', 'c.subscription_id')
                ->join('subscription_issues as d', 'c.subscription_vol_id', '=', 'd.subscription_vol_id')
                ->where('b.status', 'Active')
                ->whereDate('d.receive_by_date', '<', now()->toDateString())
                ->where('d.status', '!=', 'Received')
                ->orderby('d.receive_by_date', 'desc');

            if (!empty($periodical_id)) {
                $query->where('a.periodical_id', $periodical_id);
            }

            $data = $query->select(
                'a.*',
                'b.*',
                'c.*',
                'd.*'
            )->get();

            return response()->json([
                'status' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reminder, Server error',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Periodical Not Received Report
     */
    // ----------------------------------------------
    // ------------------------------------
    // Library Reports
    public function booksReport(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $status = $request->input('status');
            $category_group_id = $request->input('category_group_id');
            $category_id = $request->input('category_id');
            $author = $request->input('author');
            $title = $request->input('title');
            $isNew = $request->input('is_new');
            $accession_no = $request->input('accession_no');
            $location_of_book = $request->input('location_of_book');

            $query = DB::table('book')
                ->join('book_copies', 'book.book_id', '=', 'book_copies.book_id')
                ->join('category', 'category.category_id', '=', 'book.category_id')
                ->orderby('book_copies.added_date', 'desc')
                ->select(
                    'book.*',
                    'book_copies.*',
                    'category.category_name',
                    'category.call_no',
                );

            if (!empty($status)) {
                $query->where('book_copies.status', $status);
            }

            if (!empty($category_group_id)) {
                $query
                    ->join('category_categorygroup as b', 'category.category_id', '=', 'b.category_id')
                    ->where('b.category_group_id', $category_group_id);
            }

            if (!empty($category_id)) {
                $query->where('book.category_id', $category_id);
            }

            if (!empty($author)) {
                $query->where('book.author', 'like', '%' . $author . '%');
            }

            if (!empty($title)) {
                $query->where('book.book_title', 'like', '%' . $title . '%');
            }

            if (!empty($isNew) && $isNew == true) {
                $query->whereRaw('DATEDIFF(NOW(), book_copies.added_date) <= 60');
            }

            if (!empty($accession_no)) {
                $query->where('book_copies.copy_id', $accession_no);
            }

            // if (!empty($location_of_book)) {
            //     $query->where('book.location_of_book', $location_of_book);
            // }

            if (!empty($location_of_book)) {
                $query->where('book.location_of_book', 'like', '%' . $location_of_book . '%');
            }

            $books = $query->get();

            if ($books->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No books found.',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Books fetched successfully.',
                'data' => $books,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'success' => false
            ]);
        }
    }

    public function getAllRacks()
    {
        try {
            $locations = DB::table('book as a')
                ->join('book_copies as b', 'a.book_id', '=', 'b.book_id')
                ->whereNotNull('a.location_of_book')
                ->where('a.location_of_book', '!=', '')
                ->pluck('a.location_of_book');

            $racks = $locations
                ->map(function ($location) {
                    $rack = explode('/', $location)[0];
                    $rack = preg_replace('/\s+/u', ' ', trim($rack));
                    $rack = strtoupper($rack);

                    return [
                        'label' => $rack,
                        'value' => $rack,
                    ];
                })
                ->unique('value')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $racks
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function locationWiseBookReport(Request $request)
    {
        try {
            $this->authenticateUser();

            $rack = $request->input('rack');
            $shelf = $request->input('shelf');

            if ($rack == '') {
                $location_of_book_req_val = '';
            }
            if ($shelf == '') {
                // echo "strpos".strpos($rack,"Rack");
                if (strpos($rack, 'Rack') !== false) {
                    $location_of_book_req_val = str_replace("'", "\'", $rack) . '/' . '%';
                } else {
                    $location_of_book_req_val = str_replace("'", "\'", $rack) . '%';
                }
            } else {
                $location_of_book_req_val = str_replace("'", "\'", $rack) . '/' . $shelf;
            }

            $query = DB::select("SELECT a.*,b.* FROM book as a join book_copies as b on a.book_id = b.book_id where replace(a.location_of_book,' ','') LIKE replace('$location_of_book_req_val',' ','') order by b.added_date desc");  // where condition

            return response()->json([
                'status' => 200,
                'message' => 'Location-wise book report fetched successfully',
                'data' => $query,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while fetching location-wise book report',
                'success' => false
            ]);
        }
    }

    public function getSourceOfBooks(Request $request)
    {
        try {
            $this->authenticateUser();

            $sources = DB::table('book_copies')
                ->select(
                    DB::raw('DISTINCT source_of_book as value'),
                    DB::raw('source_of_book as label')
                )
                ->whereNotNull('source_of_book')
                ->where('source_of_book', '!=', '')
                ->orderBy('source_of_book', 'ASC')
                ->get();

            if ($sources->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No source of books found',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Source of books fetched successfully',
                'data' => $sources,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error($e);

            return response()->json([
                'status' => 500,
                'message' => 'Error while fetching source of books',
                'success' => false
            ]);
        }
    }

    public function booksAddedReport(Request $request)
    {
        try {
            $this->authenticateUser();

            $categoryGroupId = $request->input('category_group_id');
            $categoryId = $request->input('category_id');
            $searchFrom = $request->input('search_from');
            $searchTo = $request->input('search_to');
            $sourceOfBook = $request->input('source_of_book');

            $books = DB::table('book as a')
                ->distinct()
                ->select(
                    'book_copies.copy_id',
                    'book_copies.*',
                    'a.*'
                )
                ->join('book_copies', 'a.book_id', '=', 'book_copies.book_id')
                ->join('category as c', 'c.category_id', '=', 'a.category_id')
                ->join(
                    'category_categorygroup as d',
                    'c.category_id',
                    '=',
                    'd.category_id'
                )
                ->when($searchFrom, function ($q) use ($searchFrom) {
                    $q->whereDate('book_copies.added_date', '>=', $searchFrom);
                })
                ->when($searchTo, function ($q) use ($searchTo) {
                    $q->whereDate('book_copies.added_date', '<=', $searchTo);
                })
                ->when($categoryId, function ($query) use ($categoryId) {
                    $query->where('a.category_id', $categoryId);
                })
                ->when($categoryGroupId, function ($query) use ($categoryGroupId) {
                    $query->where('d.category_group_id', $categoryGroupId);
                })
                ->when($sourceOfBook, function ($query) use ($sourceOfBook) {
                    $query->where('book_copies.source_of_book', 'LIKE', '%' . trim($sourceOfBook) . '%');
                })
                ->orderBy('book_copies.added_date', 'DESC')
                ->get();

            if ($books->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No books found for the given filters',
                    'success' => false
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Books added report fetched successfully',
                'data' => $books,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'success' => false
            ]);
        }
    }

    public function getBooksIssueReport(Request $request)
    {
        try {
            $this->authenticateUser();

            $mType = $request->input('m_type');  // ONLY mandatory
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $memberId = $request->input('member_id');
            $searchFrom = $request->input('search_from');
            $searchTo = $request->input('search_to');
            $grnNo = $request->input('grn_no');

            /* ================= STUDENT ================= */
            if ($mType === 'S') {
                $query = DB::table('issue_return as a')
                    ->join('book', 'a.book_id', '=', 'book.book_id')
                    ->join('student', 'a.member_id', '=', 'student.student_id')
                    ->join('book_copies', 'a.copy_id', '=', 'book_copies.book_id')
                    ->select(
                        'a.*',
                        'book.book_title',
                        'student.first_name',
                        'student.mid_name',
                        'student.last_name'
                    )
                    ->orderby('a.issue_date', 'desc')
                    ->when($grnNo, function ($q) use ($grnNo) {
                        $q->where('student.reg_no', $grnNo);
                    })
                    ->when(!$grnNo && $classId, function ($q) use ($classId) {
                        $q->where('student.class_id', $classId);
                    })
                    ->when(!$grnNo && $sectionId, function ($q) use ($sectionId) {
                        $q->where('student.section_id', $sectionId);
                    })
                    ->when(!$grnNo && $memberId, function ($q) use ($memberId) {
                        $q->where('a.member_id', $memberId);
                    })
                    ->when($searchFrom, function ($q) use ($searchFrom) {
                        $q->whereDate('a.issue_date', '>=', $searchFrom);
                    })
                    ->when($searchTo, function ($q) use ($searchTo) {
                        $q->whereDate('a.issue_date', '<=', $searchTo);
                    });
            }

            /* ================= TEACHER ================= */
            if ($mType === 'T') {
                $query = DB::table('issue_return as a')
                    ->join('book', 'a.book_id', '=', 'book.book_id')
                    ->join('teacher', 'a.member_id', '=', 'teacher.teacher_id')
                    ->join('book_copies', 'a.copy_id', '=', 'book_copies.book_id')
                    ->select(
                        'a.*',
                        'book.book_title',
                        'teacher.name',
                        DB::raw('NULL as mid_name'),
                        DB::raw('NULL as last_name'),
                        DB::raw('NULL as classname'),
                        DB::raw('NULL as secname')
                    )
                    ->orderby('a.issue_date', 'desc')
                    ->when($memberId, function ($q) use ($memberId) {
                        $q->where('a.member_id', $memberId);
                    })
                    ->when($searchFrom, function ($q) use ($searchFrom) {
                        $q->whereDate('a.issue_date', '>=', $searchFrom);
                    })
                    ->when($searchTo, function ($q) use ($searchTo) {
                        $q->whereDate('a.issue_date', '<=', $searchTo);
                    });
            }

            $result = $query->get();

            if ($result->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No book history found'
                ]);
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Book issued report fetched successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getBookNotReturnReport(Request $request)
    {
        try {
            $this->authenticateUser();

            $mType = $request->input('m_type');  // mandatory
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $memberId = $request->input('member_id');
            $searchFrom = $request->input('search_from');
            $searchTo = $request->input('search_to');
            $grnNo = $request->input('grn_no');

            /* ================= STUDENT ================= */
            if ($mType === 'S') {
                $query = DB::table('issue_return as a')
                    ->join('book', 'a.book_id', '=', 'book.book_id')
                    ->join('student', 'a.member_id', '=', 'student.student_id')
                    ->join('book_copies', 'a.copy_id', '=', 'book_copies.book_id')
                    ->select(
                        'a.*',
                        'book.book_title',
                        'student.first_name',
                        'student.mid_name',
                        'student.last_name'
                    )
                    ->orderby('a.issue_date', 'desc')
                    // NOT RETURNED CONDITION
                    ->where('a.return_date', '0000-00-00')
                    ->when($grnNo, function ($q) use ($grnNo) {
                        $q->where('student.reg_no', $grnNo);
                    })
                    ->when(!$grnNo && $classId, function ($q) use ($classId) {
                        $q->where('student.class_id', $classId);
                    })
                    ->when(!$grnNo && $sectionId, function ($q) use ($sectionId) {
                        $q->where('student.section_id', $sectionId);
                    })
                    ->when(!$grnNo && $memberId, function ($q) use ($memberId) {
                        $q->where('a.member_id', $memberId);
                    })
                    ->when($searchFrom, function ($q) use ($searchFrom) {
                        $q->whereDate('a.issue_date', '>=', $searchFrom);
                    })
                    ->when($searchTo, function ($q) use ($searchTo) {
                        $q->whereDate('a.issue_date', '<=', $searchTo);
                    });
            }

            /* ================= TEACHER ================= */
            if ($mType === 'T') {
                $query = DB::table('issue_return as a')
                    ->join('book', 'a.book_id', '=', 'book.book_id')
                    ->join('teacher', 'a.member_id', '=', 'teacher.teacher_id')
                    ->join('book_copies', 'a.copy_id', '=', 'book_copies.book_id')
                    ->select(
                        'a.*',
                        'book.book_title',
                        'teacher.name',
                        DB::raw('NULL as mid_name'),
                        DB::raw('NULL as last_name'),
                        DB::raw('NULL as classname'),
                        DB::raw('NULL as secname')
                    )
                    ->orderby('a.issue_date', 'desc')
                    // NOT RETURNED CONDITION
                    ->where('a.return_date', '0000-00-00')
                    ->when($memberId, function ($q) use ($memberId) {
                        $q->where('a.member_id', $memberId);
                    })
                    ->when($searchFrom, function ($q) use ($searchFrom) {
                        $q->whereDate('a.issue_date', '>=', $searchFrom);
                    })
                    ->when($searchTo, function ($q) use ($searchTo) {
                        $q->whereDate('a.issue_date', '<=', $searchTo);
                    });
            }

            $result = $query->get();

            if ($result->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No not-issued book records found'
                ]);
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Book not issued report fetched successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function issuedBooksHistory(Request $request)
    {
        $copyId = $request->input('copy_id');
        $memberType = $request->input('m_type');  // S or T
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $studentQuery = DB::table('issue_return as a')
            ->select(
                'a.*',
                'book.book_title',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'class.name as classname',
                'section.name as secname'
            )
            ->join('book', 'a.book_id', '=', 'book.book_id')
            ->join('student', 'a.member_id', '=', 'student.student_id')
            ->join('class', 'student.class_id', '=', 'class.class_id')
            ->join('section', 'student.section_id', '=', 'section.section_id')
            ->where('a.member_type', 'S');

        $teacherQuery = DB::table('issue_return as a')
            ->select(
                'a.*',
                'book.book_title',
                'teacher.name as first_name',
                DB::raw('NULL as mid_name'),
                DB::raw('NULL as last_name'),
                DB::raw('NULL as classname'),
                DB::raw('NULL as secname')
            )
            ->join('book', 'a.book_id', '=', 'book.book_id')
            ->join('teacher', 'a.member_id', '=', 'teacher.teacher_id')
            ->where('a.member_type', 'T');

        if ($copyId) {
            $studentQuery->where('a.copy_id', $copyId);
            $teacherQuery->where('a.copy_id', $copyId);
        }

        if ($fromDate && $toDate) {
            $studentQuery->whereBetween('a.issue_date', [$fromDate, $toDate]);
            $teacherQuery->whereBetween('a.issue_date', [$fromDate, $toDate]);
        }

        if ($memberType === 'S') {
            $data = $studentQuery->orderBy('a.issue_date')->get();
        } elseif ($memberType === 'T') {
            $data = $teacherQuery->orderBy('a.issue_date')->get();
        } else {
            $data = $studentQuery
                ->unionAll($teacherQuery)
                ->orderBy('issue_date', 'desc')
                ->get();
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getIssuedBooksMonthly(Request $request)
    {
        try {
            $monthYear = $request->input('month_year'); // format: YYYY-MM

            // Student issued books
            $studentQuery = DB::table('issue_return as a')
                ->select(
                    'a.*',
                    'book.book_title',
                    'student.first_name',
                    'student.mid_name',
                    'student.last_name',
                    'b.name as classname',
                    'c.name as secname'
                )
                ->join('book', 'a.book_id', '=', 'book.book_id')
                ->join('student', 'a.member_id', '=', 'student.student_id')
                ->join('class as b', 'student.class_id', '=', 'b.class_id')
                ->join('section as c', 'student.section_id', '=', 'c.section_id')
                ->where('a.member_type', 'S')
                ->where('a.issue_date', 'like', $monthYear . '-%');

            // Teacher issued books
            $teacherQuery = DB::table('issue_return as a')
                ->select(
                    'a.*',
                    'book.book_title',
                    'teacher.name as first_name',
                    DB::raw('NULL as mid_name'),
                    DB::raw('NULL as last_name'),
                    DB::raw('NULL as classname'),
                    DB::raw('NULL as secname')
                )
                ->join('book', 'a.book_id', '=', 'book.book_id')
                ->join('teacher', 'a.member_id', '=', 'teacher.teacher_id')
                ->where('a.member_type', 'T')
                ->where('a.issue_date', 'like', $monthYear . '-%');

            // UNION + ORDER BY
            $data = $studentQuery
                ->unionAll($teacherQuery)
                ->orderBy('issue_date', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function subscriptionReminderReport()
    {
        $data = DB::table('subscription as s')
            ->join('periodicals as p', 'p.periodical_id', '=', 's.periodical_id')
            ->where('s.status', 'Active')
            ->whereRaw('s.to_date - 7 < CURDATE()')
            ->select('s.*', 'p.*')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function subscriptionReminderMail(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');

        $subscriptionIds = $request->input('subscriptionId');
        $message = $request->input('message');

        foreach ($subscriptionIds as $subId) {

            $subscription = DB::table('subscription as s')
                ->join('periodicals as p', 'p.periodical_id', '=', 's.periodical_id')
                ->where('s.subscription_id', $subId)
                ->select('s.*', 'p.*')
                ->first();

            if (!$subscription) {
                continue;
            }

            $email = $subscription->email_ids ?? null;
            $title = $subscription->title ?? null;
            $subscriptionNo = $subscription->subscription_no ?? null;

            if ($email && $title && $subscriptionNo) {

                $subject = "Subscription Reminder ";
                // :- {$title} - {$subscriptionNo}";

                $mailData = [
                    'subject' => $subject,
                    'textmsg' => $message,
                ];

                smart_mail(
                    $email,
                    $subject,
                    'emails.subscription_reminder',
                    $mailData
                );
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Subscription reminder emails sent successfully.',
            'success' => true
        ]);
    }

    public function periodicalNotReceivedReminder(Request $request)
    {
        $periodicalId = $request->periodical_id;

        $query = DB::table('periodicals as a')
            ->join('subscription as b', 'a.periodical_id', '=', 'b.periodical_id')
            ->join('subscription_volume as c', 'b.subscription_id', '=', 'c.subscription_id')
            ->join('subscription_issues as d', 'c.subscription_vol_id', '=', 'd.subscription_vol_id')
            ->where('b.status', 'Active')
            ->whereDate('d.receive_by_date', '<', Carbon::today())
            ->where('d.status', '!=', 'Received');

        // Optional periodical filter
        if (!empty($periodicalId)) {
            $query->where('a.periodical_id', $periodicalId);
        }

        $data = $query->select(
            'a.*',
            'b.*',
            'c.*',
            'd.*'
        )->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }


    public function periodicalReminderMail(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');

        $periodicalsIds = $request->input('periodicalId');
        $message = $request->input('message');

        foreach ($periodicalsIds as $subId) {

            $periodicals = DB::table('periodicals as a')
                ->join('subscription as b', 'a.periodical_id', '=', 'b.periodical_id')
                ->join('subscription_volume as c', 'b.subscription_id', '=', 'c.subscription_id')
                ->join('subscription_issues as d', 'c.subscription_vol_id', '=', 'd.subscription_vol_id')
                ->where('d.subscription_issue_id', $subId)
                ->select('a.*', 'b.*', 'c.*', 'd.*')
                ->first();

            if (!$periodicals) {
                continue;
            }

            $email = $periodicals->email_ids ?? null;
            $title = $periodicals->title ?? null;
            $subscriptionNo = $periodicals->subscription_issue_id ?? null;

            if ($email && $title && $subscriptionNo) {

                $subject = "Periodicals Reminder ";
                // :- {$title} - {$subscriptionNo}";

                $mailData = [
                    'subject' => $subject,
                    'textmsg' => $message,
                ];

                smart_mail(
                    $email,
                    $subject,
                    'emails.subscription_reminder',
                    $mailData
                );
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Periodicals reminder emails sent successfully.',
            'success' => true
        ]);
    }


    public function pendingOverdueBooks()
    {
        try {
            $this->authenticateUser();

            $query = DB::table('issue_return as a')
                ->join('book', 'a.book_id', '=', 'book.book_id')
                ->join('book_copies', 'a.copy_id', '=', 'book_copies.book_id')
                ->whereDate('a.due_date', '<', Carbon::today())
                ->where(function ($query) {
                    $query->whereNull('a.return_date')
                        ->orWhere('a.return_date', '0000-00-00');
                })

                ->leftJoin('student', function ($join) {
                    $join->on('a.member_id', '=', 'student.student_id')
                        ->where('a.member_type', '=', 'S');
                })

                ->leftJoin('teacher', function ($join) {
                    $join->on('a.member_id', '=', 'teacher.teacher_id')
                        ->where('a.member_type', '=', 'T');
                })

                ->select(
                    'a.*',
                    'book.book_title',

                    DB::raw("
                    CASE 
                        WHEN a.member_type = 'S' THEN student.first_name
                        WHEN a.member_type = 'T' THEN teacher.name
                    END as first_name
                "),

                    DB::raw("
                    CASE 
                        WHEN a.member_type = 'S' THEN student.mid_name
                        ELSE NULL
                    END as mid_name
                "),

                    DB::raw("
                    CASE 
                        WHEN a.member_type = 'S' THEN student.last_name
                        ELSE NULL
                    END as last_name
                ")
                );

            $result = $query->get();

            if ($result->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No pending or overdue book records found'
                ]);
            }

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Pending / overdue book report fetched successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    public function returnBooksPendingSeperate()
    {
        try {
            $this->authenticateUser();

            //         $result = DB::table('issue_return as a')
            //             ->join('book', 'a.book_id', '=', 'book.book_id')
            //             ->join('book_copies', 'a.copy_id', '=', 'book_copies.copy_id')
            //             ->whereDate('a.due_date', '<', Carbon::today())
            //             ->where(function ($query) {
            //                 $query->whereNull('a.return_date')
            //                     ->orWhere('a.return_date', '0000-00-00');
            //             })
            //             ->leftJoin('student', function ($join) {
            //                 $join->on('a.member_id', '=', 'student.student_id')
            //                     ->where('a.member_type', '=', 'S');
            //             })
            //             ->leftJoin('teacher', function ($join) {
            //                 $join->on('a.member_id', '=', 'teacher.teacher_id')
            //                     ->where('a.member_type', '=', 'T');
            //             })

            //             ->select(
            //                 'a.*',
            //                 'book.book_title',

            //                 DB::raw("
            //     CASE 
            //         WHEN a.member_type = 'S' THEN student.first_name
            //         WHEN a.member_type = 'T' THEN teacher.name
            //     END as first_name
            // "),

            //                 DB::raw("
            //     CASE 
            //         WHEN a.member_type = 'S' THEN student.mid_name
            //         ELSE NULL
            //     END as mid_name
            // "),

            //                 DB::raw("
            //     CASE 
            //         WHEN a.member_type = 'S' THEN student.last_name
            //         ELSE NULL
            //     END as last_name
            // "),

            //                 DB::raw("
            //     CASE 
            //         WHEN a.member_type = 'S' THEN student.emergency_contact
            //         WHEN a.member_type = 'T' THEN teacher.phone
            //     END as phone_no
            // ")
            //             )
            //             ->get();

            $result = DB::table('issue_return as a')
                ->join('book', 'a.book_id', '=', 'book.book_id')
                ->join('book_copies', 'a.copy_id', '=', 'book_copies.copy_id')

                // ADD THIS
                ->leftJoin('contact_details as b', 'a.member_id', '=', 'b.id')

                ->whereDate('a.due_date', '<', Carbon::today())
                ->where(function ($query) {
                    $query->whereNull('a.return_date')
                        ->orWhere('a.return_date', '0000-00-00');
                })

                ->leftJoin('student', function ($join) {
                    $join->on('a.member_id', '=', 'student.student_id')
                        ->where('a.member_type', '=', 'S');
                })

                ->leftJoin('teacher', function ($join) {
                    $join->on('a.member_id', '=', 'teacher.teacher_id')
                        ->where('a.member_type', '=', 'T');
                })

                ->select(
                    'a.*',
                    'book.book_title',

                    DB::raw("
            CASE 
                WHEN a.member_type = 'S' THEN student.first_name
                WHEN a.member_type = 'T' THEN teacher.name
            END as first_name
        "),

                    DB::raw("
            CASE 
                WHEN a.member_type = 'S' THEN student.mid_name
                ELSE NULL
            END as mid_name
        "),

                    DB::raw("
            CASE 
                WHEN a.member_type = 'S' THEN student.last_name
                ELSE NULL
            END as last_name
        "),

                    // UPDATED PHONE SOURCE
                    'b.phone_no as phone_no'
                )
                ->get();

            if ($result->isEmpty()) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'message' => 'No pending or overdue book records found'
                ]);
            }

            // Separate Students and Staff
            $students = $result->where('member_type', 'S')->values();
            $staff    = $result->where('member_type', 'T')->values();

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Pending / overdue book report fetched successfully',
                'data' => [
                    'students' => $students,
                    'staff'    => $staff
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function returnBooksPendingWhatsapp(Request $request)
    {
        $user = $this->authenticateUser();
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $members = $request->input('member_id');
        $message = $request->input('message');
        $schoolsettings = getSchoolSettingsData();
        $whatsappintegration = $schoolsettings->whatsapp_integration;
        $smsintegration = $schoolsettings->sms_integration;

        if ($whatsappintegration === 'Y' || $smsintegration === 'Y') {
            ReturnPendingBookJob::dispatch($members, $message);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Messages for return pending book.',
            'success' => true
        ]);
    }


    public function libraryDashboard(Request $request)
    {
        $academicYear = JWTAuth::getPayload()->get('academic_year');
        $periodicalId = $request->periodical_id;

        /** Subscription Expiry (Next 7 Days) */
        $subscriptionExpiryCount = DB::table('subscription')
            ->where('status', 'Active')
            ->whereDate('to_date', '<', Carbon::today()->addDays(7))
            ->count();

        /** Periodical Not Received */
        $periodicalNotReceivedQuery = DB::table('periodicals as a')
            ->join('subscription as b', 'a.periodical_id', '=', 'b.periodical_id')
            ->join('subscription_volume as c', 'b.subscription_id', '=', 'c.subscription_id')
            ->join('subscription_issues as d', 'c.subscription_vol_id', '=', 'd.subscription_vol_id')
            ->where('b.status', 'Active')
            ->whereDate('d.receive_by_date', '<', Carbon::today())
            ->where('d.status', '!=', 'Received');

        if (!empty($periodicalId)) {
            $periodicalNotReceivedQuery->where('a.periodical_id', $periodicalId);
        }

        $periodicalNotReceivedCount = $periodicalNotReceivedQuery->count();

        /** Pending Book Returns */
        $pendingBookReturnCount = DB::table('issue_return as ir')
            ->whereDate('ir.due_date', '<', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('ir.return_date')
                    ->orWhere('ir.return_date', '0000-00-00');
            })
            ->count();

        $baseQuery = DB::table('issue_return as a')
            ->join('book', 'a.book_id', '=', 'book.book_id')
            ->join('book_copies', 'a.copy_id', '=', 'book_copies.copy_id')
            ->whereDate('a.due_date', '<', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('a.return_date')
                    ->orWhere('a.return_date', '0000-00-00');
            });

        //  Count (same logic as list)
        $pendingStudentBookReturnCount = (clone $baseQuery)
            ->where('a.member_type', 'S')
            ->count();

        $pendingStaffBookReturnCount = (clone $baseQuery)
            ->where('a.member_type', 'T')
            ->count();

        /** Books Count */
        $totalBooksCount = DB::table('book_copies')->count();

        $availableBooksCount = DB::table('book_copies')
            ->where('status', 'A')
            ->count();

        /** Periodicals Count */
        $periodicalsCount = DB::table('periodicals')->count();

        /** Library Members Count */
        // Student Members Count
        $studentCount = DB::table('library_member as lm')
            ->join('student as s', 's.student_id', '=', 'lm.member_id')
            ->where('lm.member_type', 'S')
            ->where('s.academic_yr', $academicYear)
            ->count();

        // Teacher Members Count
        $teacherCount = DB::table('library_member as lm')
            ->join('teacher as t', 't.teacher_id', '=', 'lm.member_id')
            ->where('lm.member_type', 'T')
            ->distinct('t.teacher_id')
            ->count('t.teacher_id');

        return response()->json([
            'status' => true,
            'counts' => [
                // 'db_name' => DB::connection()->getDatabaseName(),
                // 'host'    => DB::connection()->getConfig('host'),
                'subscription_expiry'     => $subscriptionExpiryCount,
                'periodical_not_received' => $periodicalNotReceivedCount,

                'book_return_pending'     => $pendingBookReturnCount,
                'student_book_return_pending' => $pendingStudentBookReturnCount,
                'staff_book_return_pending' => $pendingStaffBookReturnCount,

                'total_books'             => $totalBooksCount,
                'available_books'         => $availableBooksCount,

                'periodicals'             => $periodicalsCount,

                'student_members'         => $studentCount,
                'teacher_members'         => $teacherCount

            ]
        ]);
    }


    // Health Acitivity Record Dev Name - Mahima Chaudhari  14-03-2026

    // Changed by Mahima  30-03-2026
    public function generateHealthActivityCsv(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_year = JWTAuth::getPayload()->get('academic_year');
        $request->validate([


            'class_id' => 'required|integer',
            'section_id' => 'required|integer'
        ]);

        $class_id = $request->class_id;
        $section_id = $request->section_id;


        // ================= STUDENTS =================
        $students = DB::table('student')
            ->select('student_id', 'roll_no', 'first_name', 'mid_name', 'last_name')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->orderBy('roll_no')
            ->get();

        // ================= PARAMETERS =================
        $parameters = DB::table('health_activity_parameter')
            ->where('is_active', 'Y')
            ->orderBy('group_id')
            ->orderBy('sequence')
            ->get();

        // ================= GROUPS =================
        $groups = DB::table('health_activity_group')
            ->orderBy('id')
            ->get();

        $groupedParams = $parameters->groupBy('group_id');

        // ================= RECORDS (Latest per student + academic year) =================
        $records = DB::table('health_activity_record as r1')
            ->select('r1.student_id', 'r1.value')
            ->where('r1.academic_yr', $academic_year)
            ->whereRaw('r1.ha_id = (
            SELECT MAX(r2.ha_id)
            FROM health_activity_record r2
            WHERE r2.student_id = r1.student_id
            AND r2.academic_yr = ?
        )', [$academic_year])
            ->get()
            ->keyBy('student_id');

        $filename = "Health_Activity_{$academic_year}_Class_{$class_id}_Section_{$section_id}.csv";

        return response()->stream(function () use ($students, $groups, $groupedParams, $records) {

            $file = fopen('php://output', 'w');

            // ================= HEADER =================
            $header = [
                'Code',
                'Roll No',
                'First Name',
                'Middle Name',
                'Last Name'
            ];

            foreach ($groups as $group) {
                if (isset($groupedParams[$group->id])) {
                    foreach ($groupedParams[$group->id] as $param) {
                        $header[] = $param->name;
                    }
                }
            }

            fputcsv($file, $header);

            // ================= ROWS =================
            foreach ($students as $student) {

                // Default empty values
                $paramValues = [];

                foreach ($groupedParams as $params) {
                    foreach ($params as $param) {
                        $paramValues[$param->name] = '';
                    }
                }

                // Fill from JSON (latest record only)
                if (isset($records[$student->student_id])) {

                    $jsonData = json_decode($records[$student->student_id]->value, true);

                    if (is_array($jsonData)) {
                        foreach ($jsonData as $key => $value) {
                            $paramValues[$key] = $value;
                        }
                    }
                }

                // Base student row
                $row = [
                    $student->student_id,
                    $student->roll_no,
                    $student->first_name,
                    $student->mid_name,
                    $student->last_name
                ];

                // Add parameter values (group + sequence order)
                foreach ($groups as $group) {
                    if (isset($groupedParams[$group->id])) {
                        foreach ($groupedParams[$group->id] as $param) {
                            $row[] = $paramValues[$param->name] ?? '';
                        }
                    }
                }

                fputcsv($file, $row);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function uploadHealthActivityRecord(Request $request)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->first(),
            ]);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $row = 1;
        $errors = [];
        $headers = [];

        // Fetch parameters from DB
        $parameters = DB::table('health_activity_parameter')
            ->where('is_active', 'Y')
            // ->pluck('name')
            ->pluck('test_parameter')
            ->toArray();

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {

            // ================= HEADER =================
            if ($row == 1) {

                if (trim($data[0]) != 'Code') {
                    return response()->json([
                        'status' => 422,
                        'message' => 'Invalid CSV format. Please download the correct format.'
                    ]);
                }

                $headers = $data; // store full header row
                $row++;
                continue;
            }

            // ================= STUDENT =================
            $student_id = isset($data[0]) ? trim($data[0]) : null;

            if (!$student_id) {
                $errors[] = "Row $row: Student Code is missing.";
                $row++;
                continue;
            }

            // ================= DYNAMIC PARAM VALUES =================
            $paramData = [];

            foreach ($headers as $index => $columnName) {

                $columnName = trim($columnName);

                // Skip fixed columns
                if (in_array($columnName, ['Code', 'Roll No', 'First Name', 'Middle Name', 'Last Name'])) {
                    continue;
                }

                // Only take valid parameters
                if (in_array($columnName, $parameters)) {
                    $paramData[$columnName] = $data[$index] ?? null;
                }
            }

            // ================= SAVE DATA =================

            DB::table('health_activity_record')->updateOrInsert(
                [
                    'student_id'  => $student_id,
                    'academic_yr' => $academic_yr
                ],
                [
                    'value'       => json_encode($paramData),
                    'created_by'  => $user->reg_id
                ]
            );

            $row++;
        }

        fclose($handle);

        if (!empty($errors)) {
            return response()->json([
                'status' => 422,
                'message' => implode(', ', $errors)
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Health Activity Records uploaded successfully',
            'success' => true
        ]);
    }

    public function updateHealthActivityRecord(Request $request, $student_id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        // Get JSON value from request
        $jsonValue = $request->input('value');

        if (!$jsonValue) {
            return response()->json([
                'status' => 422,
                'message' => 'Value JSON is required',
                'success' => false
            ]);
        }

        // Decode JSON
        $inputData = json_decode($jsonValue, true);

        if (!is_array($inputData)) {
            return response()->json([
                'status' => 422,
                'message' => 'Invalid JSON format',
                'success' => false
            ]);
        }

        // Fetch valid parameters
        $parameters = DB::table('health_activity_parameter')
            ->where('is_active', 'Y')
            // ->pluck('name')
            ->pluck('test_parameter')
            ->map(fn($item) => strtolower(trim($item)))
            ->toArray();

        // Filter only valid parameters
        $filteredData = [];

        foreach ($inputData as $key => $value) {
            if (in_array(strtolower(trim($key)), $parameters)) {
                $filteredData[$key] = $value;
            }
        }

        // No valid data
        if (empty($filteredData)) {
            return response()->json([
                'status' => 422,
                'message' => 'No valid parameters provided',
                'success' => false
            ]);
        }

        // Check record exists
        $record = DB::table('health_activity_record')
            ->where('student_id', $student_id)
            ->where('academic_yr', $academic_yr)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 404,
                'message' => 'Health record not found for this student',
                'success' => false
            ]);
        }

        // Decode existing JSON
        $existingData = json_decode($record->value, true) ?? [];

        // Merge old + new data
        $updatedData = array_merge($existingData, $filteredData);

        // Update
        DB::table('health_activity_record')
            ->where('student_id', $student_id)
            ->where('academic_yr', $academic_yr)
            ->update([
                'value' => json_encode($updatedData)
            ]);

        return response()->json([
            'status' => 200,
            'message' => 'Health record updated successfully',
            'success' => true
        ]);
    }


    public function deleteHealthActivityRecord($student_id)
    {
        $user = $this->authenticateUser();
        $academic_yr = JWTAuth::getPayload()->get('academic_year');

        // Step 1: Check record exists
        $exists = DB::table('health_activity_record')
            ->where('student_id', $student_id)
            ->where('academic_yr', $academic_yr)
            ->exists();

        if (!$exists) {
            return response()->json([
                'status' => 404,
                'message' => 'Health record not found',
                'success' => false
            ]);
        }

        // Step 2: Get student's class & section
        $student = DB::table('student')
            ->where('student_id', $student_id)
            ->select('class_id', 'section_id')
            ->first();

        if (!$student) {
            return response()->json([
                'status' => 404,
                'message' => 'Student not found',
                'success' => false
            ]);
        }

        // Step 3: Check publish status
        $isPublished = DB::table('health_activity_record_publish')
            ->where('class_id', $student->class_id)
            ->where('section_id', $student->section_id)
            ->where('publish', 'Y')
            ->exists();

        if ($isPublished) {
            return response()->json([
                'status' => 403,
                'message' => 'Health and activity record is published. You cannot delete it.',
                'success' => false
            ]);
        }

        // Step 4: Delete
        DB::table('health_activity_record')
            ->where('student_id', $student_id)
            ->where('academic_yr', $academic_yr)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Health activity record deleted successfully',
            'success' => true
        ]);
    }

    // 16-03-2026
    public function getHealthActivityReport(Request $request)
    {
        $academic_yr = JWTAuth::getPayload()->get('academic_year');
        $student_id = $request->student_id;
        $reg_id = $request->reg_id;

        $class_id = $request->class_id;
        $section_id = $request->section_id;

        $query = DB::table('health_activity_record')
            ->leftJoin('student', 'health_activity_record.student_id', '=', 'student.student_id')
            ->leftJoin('class', 'student.class_id', '=', 'class.class_id')
            ->leftJoin('section', 'student.section_id', '=', 'section.section_id')
            ->select(
                'health_activity_record.*',
                'student.first_name',
                'student.mid_name',
                'student.last_name',
                'student.class_id',
                'student.section_id',
                // 
                'student.roll_no',
                'class.name as class_name',
                'section.name as section_name'
            );

        // Filter by student
        if (!empty($student_id)) {
            $query->where('health_activity_record.student_id', $student_id);
        }

        // Filter by created user and academic year
        if (!empty($reg_id)) {
            $query->where('health_activity_record.created_by', $reg_id);
        }

        if (!empty($academic_yr)) {
            $query->where('health_activity_record.academic_yr', $academic_yr);
        }

        // Filter by class
        if (!empty($class_id)) {
            $query->where('student.class_id', $class_id);
        }

        // Filter by section
        if (!empty($section_id)) {
            $query->where('student.section_id', $section_id);
        }

        $data = $query->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function publishHealthActivityCard(Request $request)
    {

        try {
            $class_id = $request->class_id;
            $section_id = $request->section_id;
            $publishInput = $request->publish;

            // Toggle logic (same as your CI code)
            if ($publishInput == '') {
                $publish = 'Y';
            } else {
                if ($publishInput == 'N') {
                    $publish = 'Y';
                } else {
                    $publish = 'N';
                }
            }

            $data = [
                'class_id' => $class_id,
                'section_id' => $section_id,
                'publish' => $publish,

            ];

            // Check if record exists
            $exists = DB::table('health_activity_record_publish')
                ->where('class_id', $class_id)
                ->where('section_id', $section_id)
                ->first();

            if ($exists) {
                DB::table('health_activity_record_publish')
                    ->where('class_id', $class_id)
                    ->where('section_id', $section_id)
                    ->update($data);
            } else {

                DB::table('health_activity_record_publish')->insert($data);
            }

            // Response message (same logic)
            if ($publishInput == 'N') {
                $message = 'Health And Activity Card published successfully.';
            } else if ($publishInput == 'Y') {
                $message = 'Health And Activity Card unpublished successfully.';
            } else {
                $message = 'Health And Activity Card unpublished successfully.';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'publish_status' => $publish
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getHealthCardPublishValue($class_id, $section_id)
    {
        $data = DB::table('health_activity_record_publish')
            ->where('class_id', $class_id)
            ->where('section_id', $section_id)
            ->value('publish');

        if ($data === null) {
            return response()->json(null);
        }

        return response()->json([
            'publish_value' => $data
        ]);
    }

    public function getHealthActivityGroups()
    {
        try {
            $groups = DB::table('health_activity_group')->get();

            return response()->json([
                'status' => true,
                'message' => 'Health activity groups fetched successfully',
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function createHealthActivityParameter(Request $request)
    // {
    //     try {
    //         $test_parameter = trim($request->name);

    //         $groupId = ($request->group_id === null ||
    //             $request->group_id === "" ||
    //             $request->group_id === "null")
    //             ? 1
    //             : $request->group_id;

    //         $sequence = (int) $request->sequence;

    //         if (empty($test_parameter) || empty($sequence)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Parameter and sequence are required'
    //             ], 400);
    //         }

    //         // Check duplicate parameter
    //         $nameExists = DB::table('health_activity_parameter')
    //             ->where('test_parameter', $test_parameter)
    //             ->exists();

    //         if ($nameExists) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Parameter name already exists'
    //             ], 409);
    //         }

    //         DB::beginTransaction();

    //         // CHECK IF SEQUENCE EXISTS
    //         $sequenceExists = DB::table('health_activity_parameter')
    //             ->where('group_id', $groupId)
    //             ->where('sequence', $sequence)
    //             ->exists();

    //         // SHIFT ONLY IF EXISTS
    //         if ($sequenceExists) {
    //             DB::table('health_activity_parameter')
    //                 ->where('group_id', $groupId)
    //                 ->where('sequence', '>=', $sequence)
    //                 ->increment('sequence');
    //         }

    //         // INSERT
    //         $id = DB::table('health_activity_parameter')->insertGetId([
    //             'test_parameter' => $test_parameter,
    //             'group_id' => $groupId,
    //             'sequence' => $sequence,
    //             'is_active' => 'Y',
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Health activity parameter created successfully',
    //             'data' => [
    //                 'id' => $id,
    //                 'test_parameter' => $test_parameter,
    //                 'group_id' => $groupId,
    //                 'sequence' => $sequence
    //             ]
    //         ], 201);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function getHealthActivityParameterList()
    // {
    //     try {
    //         $data = DB::table('health_activity_parameter as p')
    //             ->join('health_activity_group as g', 'p.group_id', '=', 'g.id')
    //             ->select(
    //                 'p.id',
    //                 // 'p.name as parameter_name',
    //                 'p.test_parameter as parameter_name',
    //                 'p.group_id',
    //                 'g.group_name',
    //                 'p.sequence',
    //                 'p.is_active'
    //             )
    //             ->orderBy('p.group_id')
    //             ->orderBy('p.sequence')
    //             ->get();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Health activity parameter list fetched successfully',
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function createHealthActivityParameter(Request $request)
    {
        try {
            $test_parameter = trim($request->name);
            $description = $request->description ?? null;
            $groupId = $request->group_id ?? 1;
            $sequence = (int) $request->sequence;

            $fitnessParam = $request->fitness_parameter;
            $fitnessSubParam = $request->fitness_sub_parameter;

            if (empty($test_parameter) || empty($sequence)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter and sequence are required'
                ], 400);
            }

            DB::beginTransaction();

            // BUILD param_data JSON (IMPORTANT)
            $paramData = [];

            if (!empty($fitnessParam)) {
                $paramData = [
                    [
                        "key" => strtolower(str_replace(' ', '_', $fitnessParam)),
                        "label" => $fitnessParam,
                        "children" => []
                    ]
                ];

                if (!empty($fitnessSubParam)) {
                    $paramData[0]["children"][] = [
                        "key" => strtolower(str_replace(' ', '_', $fitnessSubParam)),
                        "label" => $fitnessSubParam
                    ];
                }
            }

            // SEQUENCE SHIFT
            $sequenceExists = DB::table('health_activity_parameter')
                ->where('group_id', $groupId)
                ->whereNull('parent_id')
                ->where('sequence', '>=', $sequence)
                ->exists();

            if ($sequenceExists) {
                DB::table('health_activity_parameter')
                    ->where('group_id', $groupId)
                    ->whereNull('parent_id')
                    ->where('sequence', '>=', $sequence)
                    ->increment('sequence');
            }

            //  FINAL INSERT
            $id = DB::table('health_activity_parameter')->insertGetId([
                'test_parameter' => $test_parameter,
                'description' => $description, //  NEW FIELD
                'group_id' => $groupId,
                'parent_id' => null,
                'sequence' => $sequence,
                'depth' => 1,
                'is_leaf' => 1,
                'is_active' => 'Y',

                //  MAIN CHANGE
                'param_data' => json_encode($paramData),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Parameter created successfully',
                'data' => [
                    'id' => $id,
                    'test_parameter' => $test_parameter,
                    'group_id' => $groupId,
                    'param_data' => $paramData
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function createHealthActivityParameter(Request $request)
    // {
    //     try {
    //         $test_parameter = trim($request->name);
    //         $groupId = $request->group_id ?? 1;
    //         $sequence = (int) $request->sequence;

    //         // OPTIONAL (just for reference, NOT inserting)
    //         $fitnessParam = $request->fitness_parameter;
    //         $fitnessSubParam = $request->fitness_sub_parameter;

    //         if (empty($test_parameter) || empty($sequence)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'Parameter and sequence are required'
    //             ], 400);
    //         }

    //         DB::beginTransaction();

    //         // ALWAYS ROOT LEVEL (NO DUPLICATE HIERARCHY)
    //         $parentId = null;
    //         $depth = 1;

    //         // OPTIONAL: store hierarchy reference (if you want later)
    //         $reference = null;

    //         if (!empty($fitnessParam)) {
    //             $reference = $fitnessParam;
    //         }

    //         if (!empty($fitnessSubParam)) {
    //             $reference = $fitnessSubParam;
    //         }

    //         // SEQUENCE SHIFT
    //         $sequenceExists = DB::table('health_activity_parameter')
    //             ->where('group_id', $groupId)
    //             ->whereNull('parent_id')
    //             ->where('sequence', '>=', $sequence)
    //             ->exists();

    //         if ($sequenceExists) {
    //             DB::table('health_activity_parameter')
    //                 ->where('group_id', $groupId)
    //                 ->whereNull('parent_id')
    //                 ->where('sequence', '>=', $sequence)
    //                 ->increment('sequence');
    //         }

    //         // FINAL INSERT (ONLY LEAF NODE)
    //         $id = DB::table('health_activity_parameter')->insertGetId([
    //             'test_parameter' => $test_parameter,
    //             'group_id' => $groupId,
    //             'parent_id' => null,
    //             'sequence' => $sequence,
    //             'depth' => 1,
    //             'is_leaf' => 1,
    //             'is_active' => 'Y',

    //             // 🔥 OPTIONAL: store reference (good for mapping UI later)
    //             'reference_name' => $reference, // add column if needed
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Parameter created successfully',
    //             'data' => [
    //                 'id' => $id,
    //                 'test_parameter' => $test_parameter,
    //                 'group_id' => $groupId
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // private function buildTree(array $elements, $parentId = null)
    // {
    //     $branch = [];

    //     foreach ($elements as $element) {

    //         if ($element['parent_id'] == $parentId) {

    //             $children = $this->buildTree($elements, $element['id']);

    //             if ($children) {
    //                 $element['children'] = $children;
    //             } else {
    //                 $element['children'] = [];
    //             }

    //             $branch[] = $element;
    //         }
    //     }

    //     return $branch;
    // }
    private function buildTree(array $elements, $parentId = null)
    {
        $branch = [];

        foreach ($elements as $element) {

            // FIX: strict comparison + handle null
            if ((int)$element['parent_id'] === (int)$parentId) {

                $children = $this->buildTree($elements, $element['id']);

                $element['children'] = $children ?: [];

                $branch[] = $element;
            }
        }

        return $branch;
    }

    public function getHealthActivityParameterList()
    {
        try {
            // $params = DB::table('health_activity_parameter as p')
            //     ->join('health_activity_group as g', 'p.group_id', '=', 'g.id')
            //     ->select(
            //         'p.id',
            //         'p.test_parameter as parameter_name',
            //         'p.group_id',
            //         'g.group_name',
            //         'p.parent_id',
            //         'p.depth',
            //         'p.sequence',
            //         'p.is_leaf',
            //         'p.is_active'
            //     )
            //     ->orderBy('p.group_id')
            //     ->orderBy('p.sequence')
            //     ->get();

            $params = DB::table('health_activity_parameter as p')
                ->join('health_activity_group as g', 'p.group_id', '=', 'g.id')
                ->select(
                    'p.id',
                    'p.test_parameter as parameter_name',
                    'p.group_id',
                    'g.group_name',
                    'p.parent_id',
                    'p.depth',
                    'p.sequence',
                    'p.is_leaf',
                    'p.is_active'
                )
                ->where('p.is_active', 'Y') // ADD THIS
                ->orderBy('p.group_id')
                ->orderBy('p.parent_id') // ADD THIS
                ->orderBy('p.sequence')
                ->get();
            // Convert to array
            $paramsArray = json_decode(json_encode($params), true);

            //  Build Tree
            $tree = $this->buildTree($paramsArray);

            return response()->json([
                'status' => true,
                'message' => 'Health activity parameter list fetched successfully',
                'data' => $tree
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateHealthActivityParameter(Request $request, $id)
    {
        try {
            $id = (int) $id;

            // map frontend -> backend
            $test_parameter = trim($request->name);

            $groupId = ($request->group_id === null ||
                $request->group_id === "" ||
                $request->group_id === "null")
                ? 1
                : $request->group_id;

            $newSequence = (int) $request->sequence;

            // Check record exists
            $existing = DB::table('health_activity_parameter')
                ->where('id', $id)
                ->first();

            if (!$existing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter not found'
                ], 404);
            }

            // ✅ Duplicate check (updated)
            $nameExists = DB::table('health_activity_parameter')
                ->where('test_parameter', $test_parameter)
                ->where('id', '!=', $id)
                ->exists();

            if ($nameExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter name already exists'
                ], 409);
            }

            $oldSequence = (int) $existing->sequence;

            DB::beginTransaction();

            // CASE 1: SAME SEQUENCE
            if ($newSequence == $oldSequence) {
                DB::table('health_activity_parameter')
                    ->where('id', $id)
                    ->update([
                        'test_parameter' => $test_parameter, // ✅ changed
                        'group_id' => $groupId
                    ]);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Updated successfully'
                ]);
            }

            // CHECK IF TARGET SEQUENCE EXISTS
            $sequenceExists = DB::table('health_activity_parameter')
                ->where('group_id', $groupId)
                ->where('sequence', $newSequence)
                ->where('id', '!=', $id)
                ->exists();

            // CASE 2: GAP EXISTS
            if (!$sequenceExists) {
                DB::table('health_activity_parameter')
                    ->where('id', $id)
                    ->update([
                        'test_parameter' => $test_parameter, // ✅ changed
                        'group_id' => $groupId,
                        'sequence' => $newSequence
                    ]);

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Updated successfully (gap used)'
                ]);
            }

            // CASE 3: MOVE UP
            if ($newSequence < $oldSequence) {
                DB::table('health_activity_parameter')
                    ->where('group_id', $groupId)
                    ->whereBetween('sequence', [$newSequence, $oldSequence - 1])
                    ->increment('sequence');
            }

            // CASE 4: MOVE DOWN
            else {
                DB::table('health_activity_parameter')
                    ->where('group_id', $groupId)
                    ->whereBetween('sequence', [$oldSequence + 1, $newSequence])
                    ->decrement('sequence');
            }

            // FINAL UPDATE
            DB::table('health_activity_parameter')
                ->where('id', $id)
                ->update([
                    'test_parameter' => $test_parameter, // changed
                    'group_id' => $groupId,
                    'sequence' => $newSequence
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteHealthActivityParameter($id)
    {
        try {
            // Check if parameter exists
            $parameter = DB::table('health_activity_parameter')
                ->where('id', $id)
                ->first();

            if (!$parameter) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter not found'
                ], 404);
            }

            // Check if parameter is used in health_activity_record
            // $isUsed = DB::table('health_activity_record')
            //     ->where('parameter_id', $id)
            //     ->exists();

            // if ($isUsed) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Parameter is already used in Health Activity Record'
            //     ], 409);
            // }

            $isUsed = DB::table('health_activity_record')
                ->whereRaw("JSON_CONTAINS_PATH(value, 'one', ?)", ['$.' . $parameter->test_parameter])
                ->exists();

            if ($isUsed) {
                return response()->json([
                    'status' => false,
                    'message' => 'Parameter is already used in Health and Activity Record'
                ], 409);
            }

            // Delete parameter
            DB::table('health_activity_parameter')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Health activity parameter deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHealthActivityParameterByGroup($group_id)
    {
        try {

            // FIX: treat null as Basic Information (id = 1)
            if ($group_id === "null" || $group_id === null || $group_id === "") {
                $group_id = 1;
            }

            // Check group exists
            $groupExists = DB::table('health_activity_group')
                ->where('id', $group_id)
                ->exists();

            if (!$groupExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            // Fetch parameters
            $data = DB::table('health_activity_parameter as p')
                ->join('health_activity_group as g', 'p.group_id', '=', 'g.id')
                ->where('p.group_id', $group_id)
                ->select(
                    'p.id',
                    // 'p.name as parameter_name',
                    'p.test_parameter as parameter_name',
                    'p.group_id',
                    'g.group_name',
                    'p.sequence'
                )
                ->orderBy('p.sequence')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Parameters fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkSequenceAvailability(Request $request)
    {
        try {
            $groupId = ($request->group_id === null ||
                $request->group_id === "" ||
                $request->group_id === "null")
                ? 1
                : $request->group_id;

            // normalize
            $sequence = number_format((float)$request->sequence, 2, '.', '');

            $base = (int)$sequence;
            $baseFormatted = number_format($base, 2, '.', '');

            // get all sequences
            $existingSequences = DB::table('health_activity_parameter')
                ->where('group_id', $groupId)
                ->pluck('sequence')
                ->map(function ($seq) {
                    return number_format((float)$seq, 2, '.', '');
                })
                ->toArray();

            // filter same base
            $related = array_filter($existingSequences, function ($seq) use ($base) {
                return floor((float)$seq) == $base;
            });

            sort($related);

            // CASE 1: base not used
            if (!in_array($baseFormatted, $related)) {
                return response()->json([
                    'status' => true,
                    'message' => 'Sequence is available',
                    'available' => true,
                    'suggested_sequence' => $baseFormatted
                ]);
            }

            // CASE 2: base exists → find next
            $found = false;
            $nextSequence = null;

            for ($i = 1; $i <= 99; $i++) {
                $newSeq = number_format($base + ($i / 100), 2, '.', '');

                if (!in_array($newSeq, $related)) {
                    $nextSequence = $newSeq;
                    $found = true;
                    break;
                }
            }

            // full
            if (!$found) {
                return response()->json([
                    'status' => false,
                    'message' => "All sequence numbers from {$base}.01 to {$base}.99 are already used",
                    'available' => false
                ], 409);
            }

            //suggest next
            return response()->json([
                'status' => true,
                'message' => "Sequence already exists. Next available is {$nextSequence}",
                'available' => false,
                'suggested_sequence' => $nextSequence
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLastSequenceByGroup(Request $request)
    {
        try {
            $groupId = ($request->group_id === null ||
                $request->group_id === "" ||
                $request->group_id === "null")
                ? 1
                : $request->group_id;

            // Get max sequence
            $lastSequence = DB::table('health_activity_parameter')
                ->where('group_id', $groupId)
                ->max('sequence');

            return response()->json([
                'status' => true,
                'group_id' => $groupId,
                'last_sequence' => $lastSequence ? (int)$lastSequence : 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleHealthActivityParameterStatus($id)
    {
        try {
            $record = DB::table('health_activity_parameter')
                ->where('id', $id)
                ->first();

            if (!$record) {
                return response()->json([
                    'status' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            // Toggle logic
            $newStatus = ($record->is_active === 'Y') ? 'N' : 'Y';

            DB::table('health_activity_parameter')
                ->where('id', $id)
                ->update([
                    'is_active' => $newStatus
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Status updated successfully',
                'data' => [
                    'id' => $id,
                    'is_active' => $newStatus
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // only paramter present group
    public function getHealthActivityGroupsWithParams()
    {
        // Fetch groups (ordered)
        $groups = DB::table('health_activity_group')
            ->orderBy('id')
            ->get();

        // Fetch parameters (ordered by group + sequence)
        $parameters = DB::table('health_activity_parameter')
            ->where('is_active', 'Y')
            ->orderBy('group_id')
            ->orderBy('sequence')
            ->get();

        // Group parameters by group_id
        $groupedParams = $parameters->groupBy('group_id');

        $result = [];

        foreach ($groups as $group) {

            //  Skip if no parameters exist for this group
            if (!isset($groupedParams[$group->id])) {
                continue;
            }

            $result[] = [
                'group_id'   => $group->id,
                'group_name' => $group->group_name,
                'parameters' => $groupedParams[$group->id]->map(function ($param) {
                    return [
                        'parameter_id'   => $param->id,
                        'parameter_name' => $param->test_parameter,
                        'sequence'       => $param->sequence
                    ];
                })->values()
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Group with parameters fetched successfully',
            'data' => $result
        ]);
    }


    public function getTeacherClasseswithSportsTeacher(Request $request)
    {
        $user = $this->authenticateUser();
        $customClaims = JWTAuth::getPayload()->get('academic_year');
        $teacher_id = $request->input('teacher_id');
        // $classdata = DB::table('subject')
        //     ->join('class', 'class.class_id', '=', 'subject.class_id')
        //     ->join('section', 'section.section_id', '=', 'subject.section_id')
        //     ->join('teacher', 'teacher.teacher_id', '=', 'subject.teacher_id')
        //     ->leftJoin('class_teachers', function ($join) use ($teacher_id) {
        //         $join
        //             ->on('class_teachers.class_id', '=', 'subject.class_id')
        //             ->on('class_teachers.section_id', '=', 'subject.section_id')
        //             ->where('class_teachers.teacher_id', '=', $teacher_id);
        //     })
        //     ->where('subject.academic_yr', $customClaims)
        //     ->where('subject.teacher_id', $teacher_id)
        //     ->where(function ($query) use ($teacher_id) {
        //         $query
        //             ->where('subject.teacher_id', $teacher_id)
        //             ->orWhere('class_teachers.teacher_id', $teacher_id);
        //     })
        //     ->distinct()
        //     ->select(
        //         'subject.class_id',
        //         'section.section_id',
        //         'class.name as classname',
        //         'section.name as sectionname',
        //         'teacher.name as teachername',
        //         'teacher.teacher_id',
        //         'class.class_id',
        //         DB::raw('CASE WHEN class_teachers.teacher_id IS NOT NULL THEN 1 ELSE 0 END as is_class_teacher')
        //     )
        //     // ->orderBy('subject.class_id')
        //     ->orderByRaw('CAST(class.name AS UNSIGNED) ASC')
        //     ->orderBy('section.name', 'ASC')
        //     ->get();


        $classdata = DB::table('subject')
            ->join('class', 'class.class_id', '=', 'subject.class_id')
            ->join('section', 'section.section_id', '=', 'subject.section_id')
            ->join('teacher', 'teacher.teacher_id', '=', 'subject.teacher_id')

            // Class teacher join
            ->leftJoin('class_teachers', function ($join) use ($teacher_id) {
                $join->on('class_teachers.class_id', '=', 'subject.class_id')
                    ->on('class_teachers.section_id', '=', 'subject.section_id')
                    ->where('class_teachers.teacher_id', '=', $teacher_id);
            })

            // Join students
            ->leftJoin('student', function ($join) {
                $join->on('student.class_id', '=', 'subject.class_id')
                    ->on('student.section_id', '=', 'subject.section_id');
            })

            // Join health activity
            ->leftJoin('health_activity_record as har', function ($join) use ($customClaims) {
                $join->on('har.student_id', '=', 'student.student_id')
                    ->where('har.academic_yr', '=', $customClaims);
            })

            ->where('subject.academic_yr', $customClaims)
            ->where('subject.teacher_id', $teacher_id)

            ->where(function ($query) use ($teacher_id) {
                $query->where('subject.teacher_id', $teacher_id)
                    ->orWhere('class_teachers.teacher_id', $teacher_id);
            })

            ->groupBy(
                'subject.class_id',
                'section.section_id',
                'class.name',
                'section.name',
                'teacher.name',
                'teacher.teacher_id',
                'class.class_id',
                'class_teachers.teacher_id'
            )

            ->select(
                'subject.class_id',
                'section.section_id',
                'class.name as classname',
                'section.name as sectionname',
                'teacher.name as teachername',
                'teacher.teacher_id',
                'class.class_id',

                DB::raw('CASE WHEN class_teachers.teacher_id IS NOT NULL THEN 1 ELSE 0 END as is_class_teacher'),

                // Total students
                DB::raw('COUNT(DISTINCT student.student_id) as total_students'),

                //Students having health records
                DB::raw('COUNT(DISTINCT har.student_id) as health_record_count')
            )

            ->orderByRaw('CAST(class.name AS UNSIGNED) ASC')
            ->orderBy('section.name', 'ASC')
            ->get();


        return response()->json([
            'status' => 200,
            'data' => $classdata,
            'message' => 'Classes for teachers.',
            'success' => true

        ]);
    }


    public function saveColumnsConfig(Request $request)
    {
        try {
            $request->validate([
                'group_id' => 'required|exists:health_activity_group,id',
                'columns_config' => 'required|array'
            ]);

            $group = DB::table('health_activity_group')
                ->where('id', $request->group_id)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            DB::table('health_activity_group')
                ->where('id', $request->group_id)
                ->update([
                    'columns_config' => json_encode($request->columns_config),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Columns config saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getColumnsConfig($group_id)
    {
        try {
            $group = DB::table('health_activity_group')
                ->where('id', $group_id)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'group_name' => $group->group_name,
                    'columns_config' => $group->columns_config
                        ? json_decode($group->columns_config, true)
                        : []
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateColumnsConfig(Request $request, $group_id)
    {
        try {
            // Validate request
            $request->validate([
                'columns_config' => 'required|array'
            ]);

            // Check if group exists
            $group = DB::table('health_activity_group')
                ->where('id', $group_id)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            // Update data
            DB::table('health_activity_group')
                ->where('id', $group_id)
                ->update([
                    'columns_config' => json_encode($request->columns_config),

                ]);

            return response()->json([
                'success' => true,
                'message' => 'Columns config updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // public function getAllGroups()
    // {
    //     try {
    //         $groups = DB::table('health_activity_group')
    //             ->select('id', 'group_name', 'columns_config')
    //             ->orderBy('id', 'asc')
    //             ->get();

    //         // Decode JSON for each group
    //         $groups->transform(function ($item) {
    //             $item->columns_config = $item->columns_config
    //                 ? json_decode($item->columns_config, true)
    //                 : [];
    //             return $item;
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'count' => $groups->count(),
    //             'data' => $groups
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function getAllGroups()
    {
        try {
            $groups = DB::table('health_activity_group')
                ->select('id', 'group_name', 'columns_config')
                ->whereNotNull('columns_config') // not NULL
                ->where('columns_config', '!=', '[]') // not empty array
                ->orderBy('id', 'asc')
                ->get();

            // Decode JSON
            $groups->transform(function ($item) {
                $item->columns_config = $item->columns_config
                    ? json_decode($item->columns_config, true)
                    : [];
                return $item;
            });

            return response()->json([
                'success' => true,
                'count' => $groups->count(),
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteGroup($group_id)
    {
        try {
            // Check if exists
            $group = DB::table('health_activity_group')
                ->where('id', $group_id)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            // Hard delete
            DB::table('health_activity_group')
                ->where('id', $group_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Group deleted permanently'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    private function removeNode($nodes, $key)
    {
        $result = [];

        foreach ($nodes as $node) {

            // skip node to delete
            if ($node['key'] === $key) {
                continue;
            }

            //  check children
            if (!empty($node['children'])) {
                $node['children'] = $this->removeNode($node['children'], $key);
            }

            $result[] = $node;
        }

        return $result;
    }

    public function deleteSubGroup(Request $request)
    {
        try {
            $group_id = $request->group_id;
            $key = $request->key; // unique key of node

            // 1. Check if used in parameter table
            $isUsed = DB::table('health_activity_parameter')
                ->where('param_data', 'LIKE', "%$key%") // OR use exact column if exists
                ->exists();

            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Sub Group is already used in parameters. Cannot delete.'
                ], 400);
            }

            // 2. Get group
            $group = DB::table('health_activity_group')
                ->where('id', $group_id)
                ->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            //  3. Decode JSON
            $columns = json_decode($group->columns_config, true);

            //  4. Remove node recursively
            $updatedColumns = $this->removeNode($columns, $key);

            //  5. Update DB
            DB::table('health_activity_group')
                ->where('id', $group_id)
                ->update([
                    'columns_config' => json_encode($updatedColumns)
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub Group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
