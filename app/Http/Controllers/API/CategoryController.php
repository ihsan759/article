<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::query()->withCount("articles")->latest()->get(['id', 'name', 'created_at', 'updated_at']);

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|max:50|unique:categories,name",
            "description" => "required"
        ], $messages = [
            "name.required" => "Wajib mengisi nama kategori",
            "name.max" => "Maksimal 50 karakter",
            "name.unique" => "Nama kategori sudah ada",
            "description.required" => "Wajib mengisi deskripsi kategori"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors(),
                "data" => ""
            ]);
        }

        $category = new Category();

        $category->fill($request->all());

        $category->save();

        return response()->json([
            "status" => true,
            "message" => "Berhasil Menambahkan kategori",
            "data" => $category
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = Category::query()->withCount("articles")->where("id", $id)->first();

        if ($category == null) {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan",
                "data" => ""
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = Category::query()->where("id", $id)->first();

        if ($category == null) {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan",
                "data" => ""
            ]);
        }

        $validator = Validator::make($request->all(), [
            "name" => [
                'required',
                'max:50',
                Rule::unique('categories', 'name')->ignore($category)
            ],
            "description" => "required"
        ], $messages = [
            "name.required" => "Wajib mengisi nama kategori",
            "name.max" => "Maksimal 50 karakter",
            "name.unique" => "Nama kategori sudah ada",
            "description.required" => "Wajib mengisi deskripsi kategori"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors(),
                "data" => ""
            ]);
        }

        $category->fill($request->all());

        $category->save();

        return response()->json([
            "status" => true,
            "message" => "Berhasil Memperbarui kategori",
            "data" => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if ($category == null) {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan",
                "data" => ""
            ]);
        }

        $category->delete();

        return response()->json([
            "status" => true,
            "message" => "Berhasil menghapus kategori",
            "data" => $category
        ]);
    }
}
