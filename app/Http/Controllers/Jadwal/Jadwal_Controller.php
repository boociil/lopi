<?php

namespace App\Http\Controllers\Jadwal;

use App\Http\Controllers\Controller;
use App\Model\Jadwal\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class Jadwal_Controller extends Controller
{
    public function __construct(Request $request)
    {

    }

    public function index(Request $request)
    {
        $today =  Carbon::today();
        $bulan_ini =  $today->month;
        $month_start = $today->startOfMonth();
        $month_end = $today->endOfMonth();

        $jadwal_bulan = Jadwal::where(function ($query) use ($bulan_ini, $month_start,  $month_end){
            $query->orWhere(function ($query1) use ($bulan_ini){
                $query1->whereNull('end_date');
                $query1->whereMonth("start_date", $bulan_ini);
            });
            $query->orWhere(function ($query1) use ( $month_start,  $month_end){
                $query1->whereNotNull('end_date');
                $query1->where(function ($query2) use ($month_start,  $month_end){
                    $query2->orWhereBetween("start_date", [$month_start->format('Y-m-d'), $month_end->format('Y-m-d')]);
                    $query2->orWhereBetween('end_date', [$month_start->format('Y-m-d'), $month_end->format('Y-m-d')]);
                });
            });
        })->get();
        return Inertia::render('beranda', [
            "jadwal" => $jadwal_bulan,
        ]);
    }

     /**
     * create
     *
     * @return void
     */
    public function create()
    {
        return inertia('Posts/Create');
    }

    /**
     * store
     *
     * @param  mixed $request
     * @return void
     */
    public function store(Request $request)
    {
        //set validation
        $request->validate([
            'title'   => 'required',
            'content' => 'required',
        ]);

        //create post
        Post::create([
            'title'     => $request->title,
            'content'   => $request->content
        ]);

        //redirect
        return redirect()->route('posts.index')->with('success', 'Data Berhasil Disimpan!');
    }

    /**
     * edit
     *
     * @param  mixed $post
     * @return void
     */
    public function edit(Post $post)
    {
        return inertia('Posts/Edit', [
            'post' => $post,
        ]);
    }

    /**
     * update
     *
     * @param  mixed $request
     * @param  mixed $post
     * @return void
     */
    public function update(Request $request, Post $post)
    {
        //set validation
        $request->validate([
            'title'   => 'required',
            'content' => 'required',
        ]);

        //update post
        $post->update([
            'title'     => $request->title,
            'content'   => $request->content
        ]);

        //redirect
        return redirect()->route('posts.index')->with('success', 'Data Berhasil Diupdate!');
    }

    /**
     * destroy
     *
     * @param  mixed $post
     * @return void
     */
    public function destroy(Post $post)
    {
        //delete post
        $post->delete();

        //redirect
        return redirect()->route('posts.index')->with('success', 'Data Berhasil Dihapus!');
    }

}
