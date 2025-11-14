<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LibraryController extends Controller
{
    
    public function createMembers(Request $request)
    {
        
        $request->validate([
            'selector' => 'required|array',
            'type' => 'required|string|max:50',
        ]);

        // 2) joining_date ke liye current time
        $now = Carbon::now()->toDateTimeString(); // 'YYYY-MM-DD HH:MM:SS'

        // 3) Loop through each selected ID and insert into DB
        foreach ($request->selector as $memberId) {
            // a) prepare data
            $data = [
                'member_id'    => $memberId,
                'member_type'  => $request->type,
                'joining_date' => $now,
                'status'       => 'A',
            ];

            // b) insert using Query Builder
            DB::table('library_member')->insert($data);
        }

        // 4) Return JSON response (API style)
        return response()->json([
            'message' => 'New Members Created Successfully!',
            'created' => count($request->selector)
        ], 201); // 201 = created
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
                ->whereNotIn('student.student_id', function($subquery) {
                    $subquery->select('library_member.member_id')
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
                ->whereNotIn('teacher.teacher_id', function($subquery) {
                    $subquery->select('library_member.member_id')
                             ->from('library_member')
                             ->where('library_member.member_type', '=', 'T');
                });
    
            if (!empty($name)) {
                $query->where('teacher.name', 'like', "%$name%")
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
        $rows = DB::table('category_group')->orderBy('category_group_name')->get();
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
        $data = DB::table('category_group')->select('category_group_id','category_group_name')->get();

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
        // Validation (group optional)
    $validator = Validator::make($request->all(), [
        'category_name' => 'required|string|max:100',
        'call_no' => 'required|string|max:50',
        'category_group_ids' => 'array|nullable' // <-- optional now
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Step 1: Update category table
    DB::table('category')->where('category_id', $id)->update([
        'category_name' => $request->category_name,
        'call_no' => $request->call_no,
    ]);

    // Step 2: If category_group_ids provided, update mapping
    if ($request->has('category_group_ids') && !empty($request->category_group_ids)) {
        // Delete old mappings
        DB::table('category_categorygroup')->where('category_id', $id)->delete();

        // Insert new mappings
        foreach ($request->category_group_ids as $groupId) {
            DB::table('category_categorygroup')->insert([
                'category_id' => $id,
                'category_group_id' => $groupId,
            ]);
        }
    }

    return response()->json(['message' => 'Category updated successfully!'], 200);
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
            ->pluck('category_group_id'); // returns an array of IDs
    
        
        $response = [
            'category_id' => $category->category_id,
            'category_name' => $category->category_name,
            'call_no' => $category->call_no,
            'category_groups' => $groupIds,
        ];
    
       
        return response()->json($response, 200);
    }
    
    public function getBookDetails(Request $request)
    {
        $book_id = $request->book_id;

        // ✅ Direct Query Builder Join
        $bookData = DB::table('book')
            ->join('book_copies', 'book.book_id', '=', 'book_copies.book_id')
            ->where('book.book_id', $book_id)
            ->select(
                'book.book_id',
                'book.book_title',
                'book.category_id',
                'book.author',
                'book.publisher',
                'book.days_borrow',
                'book.location_of_book',
                'book.issue_type',
                'book_copies.book_copies_id',
                'book_copies.copy_id',
                'book_copies.bill_no',
                'book_copies.source_of_book',
                'book_copies.isbn',
                'book_copies.year',
                'book_copies.edition',
                'book_copies.no_of_pages',
                'book_copies.price',
                'book_copies.added_date',
                'book_copies.status',
                'book_copies.IsNew'
            )
            ->get();

        if ($bookData->isEmpty()) {
            return response()->json(['message' => 'No records found'], 404);
        }

        return response()->json(['data' => $bookData], 200);
    }


    public function searchBooks(Request $request)
    {
        try {
            // Example authentication (replace with your actual method)
            // $user = $this->authenticateUser();

            $status = $request->input('status');
            $category_group_id = $request->input('category_group_id');
            $category_id = $request->input('category_id');
            $author = $request->input('author');
            $title = $request->input('title');
            $isNew = $request->input('is_new');
            $accession_no = $request->input('accession_no');

            $query = DB::table('book')
                ->join('book_copies', 'book.book_id', '=', 'book_copies.book_id')
                ->join('category', 'category.category_id', '=', 'book.category_id')
                ->select(
                    'book.*',
                    'book_copies.*',
                    'category.category_name',
                    'category.call_no'
                );

            if (!empty($status)) {
                $query->where('book_copies.status', $status);
            }

            if (!empty($category_group_id)) {
                $query->join('category_categorygroup as b', 'category.category_id', '=', 'b.category_id')
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
            if ($request->input('operation') == 'edit') {

                $data2 = $request->input('copyedit'); // accession numbers
                $data3 = $request->input('book_copies_id'); // copy record ids
                $source = $request->input('source');
                $bill = $request->input('bill_no');
                $isbn = $request->input('isbn');
                $edition = $request->input('edition');
                $price = $request->input('price');
                $no_of_pages = $request->input('no_of_pages');
                $year = $request->input('year');
                $status = $request->input('status');

                // $data = main book data
                $data = [
                    'book_title' => $request->input('book_title'),
                    'author' => $request->input('author'),
                    'category_id' => $request->input('category_id'),
                ];

                // Update main book record
                DB::table('book')->where('book_id', $book_id)->update($data);

                // Loop through book copies
                for ($i = 0; $i < count($data3); $i++) {

                    $data1 = [
                        'book_id' => $book_id,
                        'status' => 'A',
                        'copy_id' => $data2[$i],
                        'source_of_book' => $source[$i],
                        'bill_no' => $bill[$i],
                        'isbn' => $isbn[$i],
                        'edition' => $edition[$i],
                        'price' => $price[$i],
                        'no_of_pages' => $no_of_pages[$i],
                        'year' => $year[$i],
                        'added_date' => now()
                    ];

                    if ($data3[$i] == "0") {
                        // Check duplicate accession number
                        $exists = DB::table('book_copies')->where('copy_id', $data2[$i])->exists();
                        if ($exists) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Book for this accession no. already exists!'
                            ], 409);
                        }

                        DB::table('book_copies')->insert($data1);
                    } else {
                        DB::table('book_copies')
                            ->where('book_copies_id', $data3[$i])
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
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

     public function deleteBook($book_id)
    {
        try {
            // Check if book is issued
            $issued = DB::table('issue_return')
                        ->where('book_id', $book_id)
                        ->where('return_date', '0000-00-00')
                        ->exists();

            if ($issued) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Book is issued. Delete failed.'
                ], 409);
            }

            // Delete from book_copies
            DB::table('book_copies')->where('book_id', $book_id)->delete();

            // Delete from book
            DB::table('book')->where('book_id', $book_id)->delete();

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
        $m_type     = $request->input('m_type', '');
        $class_id   = $request->input('class_id', '');
        $section_id = $request->input('section_id', '');
        $name       = $request->input('name', '');
        $status     = $request->input('status', '');
        $acd_yr     = $request->input('acd_yr', '');
        $grn_no     = $request->input('grn_no', '');

        if ($m_type === 'S') {
            $query = DB::table('student')
                ->join('library_member', 'student.student_id', '=', 'library_member.member_id')
                ->select('student.*', 'library_member.*')
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
        }

        elseif ($m_type === 'T') {
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
        }

        else {
            return response()->json(['error' => 'Invalid member type'], 400);
        }

        return response()->json($data);
    }

    
    public function updateLibraryMemberStatus(Request $request)
    {
        $status_action = $request->input('action', '');     // 'Active' or 'Inactive'
        $member_id     = $request->input('member_id', '');  // student_id or teacher_id
        $member_type   = $request->input('member_type', ''); // 'S' or 'T'
    
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
}
