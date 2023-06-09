<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $category = $request->category;
        $limit = $request->has('limit') ? $request->limit : session('limit', 10);
        $articles = Article::query()->with(["categories" => function ($query) {
            $query->select("id", "name");
        }])->when($category, function ($query, $category) {
            return $query->whereHas('categories', function ($query) use ($category) {
                $query->where('id', $category);
            });
        })->paginate($limit);
        $category ? $articles->appends(['limit' => $limit, 'category' => $category]) : $articles->appends(['limit' => $limit]);
        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $articles
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
            "title" => "required|max:50|string",
            'media' => 'required|array',
            'media.*' => 'required|image|mimes:jpeg,png,jpg',
            "content" => "required|string",
            "category" => "required|exists:categories,id|integer|numeric",
            "banner" => 'required|image|mimes:jpeg,png,jpg'
        ], $messages = [
            "title.required" => "Wajib mengisi title artikel",
            "title.string" => "title hanya menerima inputan string",
            "title.max" => "Maksimal 50 karakter",
            "content.required" => "Wajib mengisi konten artikel",
            "content.string" => "Konten hanya menerima inputan string",
            "media.required" => "Wajib mengupload gambar",
            "media.array" => "Wajib mengupload gambar dalam bentuk array",
            "media.image" => "Wajib mengupload file dalam bentuk gambar",
            "media.*.image" => "Wajib mengupload file dalam bentuk gambar",
            "media.mimes" => "Hanya menerima extensi jpeg, png, jpg",
            "media.*.mimes" => "Hanya menerima extensi jpeg, png, jpg",
            "category.required" => "Wajib ada kategori",
            "category.exists" => "Kategori tidak tersedia",
            "category.integer" => "Hanya menerima inputan bilangan bulat",
            "category.numeric" => "Hanya menerima inputan numeric",
            "banner.required" => "Wajib mengupload gambar",
            "banner.image" => "Wajib mengupload file dalam bentuk gambar",
            "banner.mimes" => "Hanya menerima extensi jpeg, png, jpg",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors(),
                "data" => ""
            ]);
        }

        $article = new Article();




        $images = [];

        try {
            DB::transaction(function () use ($article, $request, $images) {

                $banner = $request->getSchemeAndHttpHost() . '/storage/' . $request->file('banner')->store('banner', 'public');

                foreach ($request->file('media') as $key => $media) {
                    $images[] = $request->getSchemeAndHttpHost() . '/storage/' . $media->store('media', 'public');
                }
                $article->fill([
                    "title" => $request->title,
                    "content" => $request->content,
                    "banner" => $banner,
                    "category_id" => $request->category
                ]);

                $article->save();

                foreach ($images as $key => $image) {
                    Media::query()->create([
                        "image" => $image,
                        "article_id" => $article->id
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage(),
                "data" => ""
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "Artikel berhasil ditambah",
            "data" => $article
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
        $article = Article::query()->with(["categories" => function ($query) {
            $query->select("id", "name");
        }, "media"])->find($id);

        if ($article == null) {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan",
                "data" => ""
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $article
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

        $article = Article::query()->with(["categories" => function ($query) {
            $query->select("id");
        }, "media"])->find($id);


        if ($article == null) {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan",
                "data" => ""
            ]);
        }

        $validator = Validator::make($request->all(), [
            "title" => "required|max:50|string",
            'media' => 'array',
            'media.*' => 'image|mimes:jpeg,png,jpg',
            "content" => "required|string",
            "category" => "required|exists:categories,id|integer",
            "banner" => 'image|mimes:jpeg,png,jpg'
        ], $messages = [
            "title.required" => "Wajib mengisi title artikel",
            "title.string" => "title hanya menerima inputan string",
            "title.max" => "Maksimal 50 karakter",
            "content.required" => "Wajib mengisi konten artikel",
            "content.string" => "Konten hanya menerima inputan string",
            "media.array" => "Wajib mengupload file gambar dalam bentuk array",
            "media.*.image" => "Wajib mengupload file dalam bentuk gambar",
            "media.*.mimes" => "Hanya menerima extensi jpeg, png, jpg",
            "category.required" => "Wajib ada kategori",
            "category.exists" => "Kategori tidak tersedia",
            "category.integer" => "Hanya menerima inputan integer",
            "banner.image" => "Wajib mengupload file dalam bentuk gambar",
            "banner.mimes" => "Hanya menerima extensi jpeg, png, jpg",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => $validator->errors(),
                "data" => ""
            ]);
        }

        $bannerImage = $request->file('banner');
        if ($bannerImage != null) {

            $path_old = str_replace($request->getSchemeAndHttpHost() . "/storage/", "", $article->banner);
            Storage::disk('public')->delete($path_old);

            $banner = $request->getSchemeAndHttpHost() . '/storage/' . $request->file('banner')->store('banner', 'public');

            $article->fill([
                "title" => $request->title,
                "content" => $request->content,
                "banner" => $banner,
                "category_id" => $request->category
            ]);
        } else {
            $article->fill([
                "title" => $request->title,
                "content" => $request->content,
                "category_id" => $request->category
            ]);
        }

        $mediaImage = $request->file('media');

        if ($mediaImage != null) {

            foreach ($article->media as $media) {
                $path_old = str_replace($request->getSchemeAndHttpHost() . "/storage/", "", $media->image);
                Storage::disk('public')->delete($path_old);
            }

            $article->media()->delete();

            $images = [];
            foreach ($request->file('media') as $key => $data) {
                $images[] = $request->getSchemeAndHttpHost() . '/storage/' . $data->store('media', 'public');
            }

            foreach ($images as $key => $image) {
                Media::query()->create([
                    "image" => $image,
                    "article_id" => $article->id
                ]);
            }
        }

        $article->save();

        return response()->json([
            "status" => true,
            "message" => "Artikel berhasil di perbarui",
            "data" => $article
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $article = Article::query()->with(["categories" => function ($query) {
            $query->select("id");
        }, "media"])->find($id);

        if ($article == null) {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan",
                "data" => ""
            ]);
        }

        try {
            DB::transaction(function () use ($article, $request) {
                foreach ($article->media as $media) {
                    $path_old = str_replace($request->getSchemeAndHttpHost() . "/storage/", "", $media->image);
                    Storage::disk('public')->delete($path_old);
                }

                $article->media()->delete();

                $banner = str_replace($request->getSchemeAndHttpHost() . "/storage/", "", $article->banner);
                Storage::disk('public')->delete($banner);

                $article->delete();
            });
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage(),
                "data" => ""
            ]);
        }

        return response()->json([
            "status" => true,
            "message" => "Artikel berhasil dihapus",
            "data" => $article
        ]);
    }
}
