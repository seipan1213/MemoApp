<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\MemoTag;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $memos = Memo::select('memos.*')->where('user_id', '=', Auth::id())->whereNUll('deleted_at')->orderBy('updated_at', 'DESC')->get();

        $tags = Tag::where('user_id', '=', Auth::id())->whereNull('deleted_at')->orderBy('id', 'DESC')->get();
        return view('create',compact('memos','tags'));
    }

    public function store(Request $request)
    {
        $posts = $request->all();

        DB::transaction(function () use($posts) {
            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => Auth::id()]);
            $tag_exists = Tag::where('user_id', '=', Auth::id())->where('name', '=', $posts['new_tag'])->exists();

            if ((!empty($posts['new_tag']) || $posts['new_tag'] === "0")&& !$tag_exists){
                $tag_id = Tag::insertGetId(['user_id'=>Auth::id(), 'name' => $posts['new_tag']]);
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }
            if(!empty($posts['tags'][0])){
                foreach($posts['tags'] as $tag){
                    MemoTag::insert(['memo_id'=> $memo_id, 'tag_id' => $tag]);
                }
            }
        });
        return redirect(route('home'));
    }

    public function edit($id)
    {
        $memos = Memo::select('memos.*')
        ->where('user_id', '=', Auth::id())
        ->whereNUll('deleted_at')
        ->orderBy('updated_at', 'DESC')
        ->get();

        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
        ->leftJoin('memo_tags', 'memo_tags.memo_id' ,'=', 'memos.id')
        ->leftJoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
        ->where('memos.user_id', '=', Auth::id())
        ->where('memos.id','=',$id)
        ->whereNull('memos.deleted_at')
        ->get();

        $include_tags = [];

        foreach($edit_memo as $memo){
            array_push($include_tags,$memo['tag_id']);
        }

        $tags = Tag::where('user_id', '=', Auth::id())
        ->whereNull('deleted_at')
        ->orderBy('id', 'DESC')
        ->get();
        return view('edit',compact('memos','edit_memo','include_tags','tags'));
    }

    public function update(Request $request)
    {
        $posts = $request->all();

        DB::transaction(function () use($posts) {
            Memo::where('id', $posts['memo_id'])-> update(['content'=>$posts['content']]);
            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();

            if (!empty($posts['tags'])){
                foreach($posts['tags'] as $tag){
                    MemoTag::insert(['memo_id' =>$posts['memo_id'], 'tag_id' => $tag]);
                }
            }

            $tag_exists = Tag::where('user_id', '=', Auth::id())->where('name', '=', $posts['new_tag'])->exists();

            if ((!empty($posts['new_tag']) || $posts['new_tag'] === "0") && !$tag_exists){
                $tag_id = Tag::insertGetId(['user_id'=>Auth::id(), 'name' => $posts['new_tag']]);
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);
            }
        });

        return redirect(route('home'));
    }

    public function destroy(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['deleted_at'=>date("Y-m-d H:i:s",time())]);
        return redirect(route('home'));
    }
}
