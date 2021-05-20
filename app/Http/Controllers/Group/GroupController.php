<?php

namespace App\Http\Controllers\Group;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Group\Group;
use App\Models\Group\GroupRole;
use App\Models\Group\GroupMember;
use App\Models\Group\GroupInfo;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;


class GroupController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('group.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate
        $validator = Validator::make($request->all(),[
            'name'=>'required|max:255',
            'password'=>'required|alpha_num|min:4|max:255|confirmed'//password_confirmation
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        //
        $group=Group::create([
            'name'=>$request->name,
        ]);
        $role=$group->roles()->create([
            'role_rank'=>0,
            'name'=>'管理者',
            'password'=>Hash::make($request->password),
        ]); 
        $group->users()->attach(Auth::id(),[
            'role_id'=>$role->id,
        ]);
        $group->infoBases()->attach(1,[
            'updated_by'=>Auth::id(),
        ]);
        $group->location()->create();
        return redirect()->route('group.show',$group->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $group=Group::find($id);
        return view('group.show')->with(['group'=>$group,'infos'=>$group->infoBases()->get()]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        return view('group.edit')->with(['group'=>Group::find($id)]);
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
        $validator = Validator::make($request->all(),[
            'name'=>'required|max:255',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        Group::find($id)->fill([
            'name'=>$request['name'],
        ])->save();
        return redirect()->route('group.show', $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //delete all relate models
        $group=Group::find($id);
        $group->info_bases()->detach();
        $group->users()->detach();
        $group->roles()->delete();
        $group->delete();
        return redirect()->route('group.home');
    }

    /**
     * ユーザ一覧
     *
     * @param  int  $group_id,$role_id
     * @return \Illuminate\Http\Response
     */
    public function users($group_id,$role_id){
        $group=Group::find($group_id);
        return view('group.users')->with([
            'group'=>$group,
            'users'=>$group->roles()->where('role_id',$role_id)->users()->get(),
            ]);  
    }

    /**
     * ロール一覧
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function roles($id){
        $group=Group::find($id);
        return view('group.roles')->with([
            'group'=>$group,
            'roles'=>$group->roles()->get(),
            ]);  
    }

    /**
     * グループをマップ表示
     * 
     * @return \Illuminate\Http\Response
     */
    public function map(){
        $all=Group::all();
        $groups=[];
        foreach($all as $group){
            $groups[]=$group->location()->get();
        }
        return view('group.map')->with([
            'groups'=>$groups,
        ]);
    }
}
