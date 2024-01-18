<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Document;
use App\Models\Department;
use App\Models\DriverInfo;
use App\Models\Truck;
use App\Models\GoodsReceive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Source;
use App\Models\Tracking;
use Symfony\Component\CssSelector\Node\FunctionNode;

class userController extends Controller
{
    private UserRepositoryInterface $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function list()
    {

        if(request('search') && !request('search_data'))
        {
            return back()->with('error','Please add search data');
        }else if(!request('search') && request('search_data')){
            return back()->with('error','Please add search method');
        }
        $ids=[];
        if(request('search') == 'truck_no' || request('search') == 'driver_name'){
            $ids = DriverInfo::where(request('search'),request('search_data'))->pluck('received_goods_id');
        }
        $data = GoodsReceive::when(request('search') == 'document_no' && request('search_data'),function($q){
                            $q->where('document_no',request('search_data'));
        })
                            ->when(request('search') != 'document_no' && request('search_data'),function($q) use($ids){
                                $q->whereIn('id',$ids);
                            })
                            ->when(request('branch'),function($q){
                                $q->where('branch_id',request('branch'));
                            })
                            ->when(request('status'),function($q){
                                $q->where('status',request('status'));
                            })
                            ->when(request('from_date'),function($q){
                                $q->where('start_date','>=',request('from_date'));
                            })
                            ->when(request('to_date'),function($q){
                                $q->where('start_date','<=',request('to_date'));
                            })
                            ->whereNotNull('total_duration')
                            ->orderBy('created_at','desc')
                            ->paginate(15);
        $branch = Branch::get();
        view()->share(['branch'=>$branch]);
        return view('user.list',compact('data'));
    }

    public function view_goods($id)
    {
        $main = GoodsReceive::where('id',$id)->first();
        $truck = Truck::get();
        $driver = DriverInfo::where('received_goods_id',$id)->get();
        $cur_driver = DriverInfo::where('received_goods_id',$id)->whereNull('duration')->first();
        $document = Document::where('received_goods_id',$id)->orderBy('id')->get();
        $status = 'view';

        return view('user.receive_goods.receive_goods',compact('main','document','driver','cur_driver','truck','status'));
    }

    public function car_info()
    {

        $id = getAuth()->id;
        $data = DriverInfo::select('driver_infos.*', 'goods_receives.user_id')
                        ->leftJoin('goods_receives', 'driver_infos.received_goods_id', 'goods_receives.id')
                        ->where('goods_receives.user_id', getAuth()->id)
                        ->whereNull('driver_infos.duration')
                        ->first();

        $emp = GoodsReceive::where('user_id',getAuth()->id)
                            ->whereNull('total_duration')
                            ->first();
        $type = Truck::get();
        if($data || $emp){
            view()->share(['truck'=>$type]);
            return redirect()->route('receive_goods', ['id' => $data->received_goods_id ?? $emp->id]);
        }else{

            $source = Source::get();
            view()->share(['truck'=>$type,'source'=>$source]);
            return view('user.receive_goods.driver_info');
        }
    }

    public function receive_goods($id)
    {
        $main = GoodsReceive::where('id',$id)->first();
        $truck = Truck::get();
        $driver = DriverInfo::where('received_goods_id',$id)->get();
        $cur_driver = DriverInfo::where('received_goods_id',$id)->whereNull('duration')->first();
        $document = Document::where('received_goods_id',$id)->orderBy('id')->get();

        // $time_start = Carbon::parse($time_str)->format('H:i:s');
        return view('user.receive_goods.receive_goods',compact('main','document','driver','cur_driver','truck'));
    }

    public function user()
    {
        $data = User::paginate(15);
        return view('user.user',compact('data'));
    }

    public function store_car_info(Request $request)
    {

        $driver = DriverInfo::where('received_goods_id',$request->main_id)->get();
        $data = $request->validate([
            'driver_name'       => 'required',
            'driver_phone'      => 'required|numeric',
            'driver_nrc'        => 'required',
            'truck_no'          => 'required',
            'truck_type'        => 'required',
        ]);
        if(count($driver) > 0){
            $driver = new DriverInfo();
            $driver->ph_no              = $request->driver_phone;
            $driver->type_truck         = $request->truck_type;
            $driver->received_goods_id  = $request->main_id;
            $driver->driver_name        = $request->driver_name;
            $driver->truck_no           = $request->truck_no;
            $driver->nrc_no             = $request->driver_nrc;
            $driver->start_date         = Carbon::now()->format('Y-m-d');
            $driver->start_time         = Carbon::now()->format('H:i:s');

            $driver->save();


        }else{

            // $branch_id = getAuth()->branch->id;

            $main               = GoodsReceive::find($request->main_id);
            $main->start_date   = Carbon::now()->format('Y-m-d');
            $main->start_time   = Carbon::now()->format('H:i:s');
            $main->save();


                $driver = new DriverInfo();
                $driver->ph_no              = $request->driver_phone;
                $driver->type_truck         = $request->truck_type;
                $driver->received_goods_id  = $main->id;
                $driver->driver_name        = $request->driver_name;
                $driver->truck_no           = $request->truck_no;
                $driver->nrc_no             = $request->driver_nrc;
                $driver->start_date         = Carbon::now()->format('Y-m-d');
                $driver->start_time         = Carbon::now()->format('H:i:s');

                $driver->save();
        }
        return redirect()->route('receive_goods',$request->main_id);

    }

    public function store_doc_info(Request $request)
    {
        $data = $request->validate([
            'source'            => 'required',
        ]);

        $same = GoodsReceive::where('start_date',Carbon::now()->format('Y-m-d'))->count();
        $shr  = 'REG'.str_replace('-', '', Carbon::now()->format('Y-m-d'));
        $branch_id = getAuth()->branch->id;
        if($same > 0){
            $name = $shr.'-'.sprintf("%04d",$same+1);
        }else{
            $name = $shr.'-'.sprintf("%04d",1);
        }

        $main               = new GoodsReceive();
        $main->document_no  = $name;
        $main->branch_id    =$branch_id;
        $main->source       = $request->source;
        $main->user_id      = getAuth()->id;
        $main->save();
        return redirect()->route('receive_goods',$main->id);
    }

    public function search_doc(Request $request)
    {

        $val = $request->data;
        $type = substr($val,0,2);
        $docs = Document::pluck('document_no')->toArray();
        if(in_array($val,$docs)){
            return response()->json(['message'=>'dublicate'],400);
        }
        // dd($type);
        $conn = DB::connection('master_product');
        if($type == "PO")
        {
            $data = $conn->select("
                select purchaseno,vendorcode,vendorname,productcode,productname,unitcount as unit,goodqty
                from  purchaseorder.po_purchaseorderhd aa
                inner join  purchaseorder.po_purchaseorderdt bb on aa.purchaseid= bb.purchaseid
                left join master_data.master_branch br on aa.brchcode= br.branch_code
                where statusflag <> 'C'
                and purchaseno= '$val'
            ");

        }else{
            $data = $conn->select("
                select tohd.transferdocno as to_docno
                ,(select branch_name_eng from master_data.master_branch br where tohd.desbrchcode = br.branch_code) as to_branch
                ,todt.productcode as product_code,todt.productname as product_name,todt.unitcount as unit
                ,todt.transferoutqty as qty
                from inventory.trs_transferouthd tohd
                left join inventory.trs_transferoutdt todt on tohd.transferid= todt.transferid
                where tohd.transferdocno in ('$val')
                and tohd.statusid <> 'C'
            ");
        }
        // dd($data);
        if($data){
            $receive = GoodsReceive::where('id', $request->id)->first();

            if (!$receive->vendor_name) {
                $receive->update([
                    'vendor_name' => $data[0]->vendorname
                ]);
            }
            $doc = Document::create([
            'document_no'       => $data[0]->purchaseno,
                'received_goods_id'  => $request->id
            ]);
            for($i = 0 ; $i < count($data) ; $i++){
                $pd_code                = new Product();
                $pd_code->document_id   = $doc->id;
                $pd_code->bar_code       = $data[$i]->productcode;
                $pd_code->supplier_name = $data[$i]->productname;
                $pd_code->qty           = (int)($data[$i]->goodqty);
                $pd_code->scanned_qty   = 0;
                $pd_code->save();
            }
            return response()->json($data,200);
        }else{
            return response()->json(['message','not found'],404);
        }
    }

    public function barcode_scan(Request $request)
    {
        $all = $request->data;
        // dd($all);
        $item= preg_replace('/\D/','',$all);
        $doc_ids = Document::where('received_goods_id',$request->id)->pluck('id');

        $product = Product::whereIn('document_id',$doc_ids)
                            ->where('bar_code',$item)
                            ->first();
        if($product){
            $doc_no = $product->doc->document_no;
            $conn = DB::connection('master_product');
            try {
                $data = $conn->select("
            select * from
            (
            select	 product_code, qty
            from	dblink('dbname=pro1_awms host = 192.168.151.241 port=5432 user=superadmin password=super123',
            '
            SELECT product_code,qty FROM (
            SELECT product_code,product_code as barcode,product_unit_rate as qty FROM public.aw_master_product_rate UNION ALL
            SELECT product_code,pack_barcode as barcode,product_unit_rate as qty FROM public.aw_master_product_rate UNION ALL
            SELECT product_code,barcode_box as barcode,unit_rate_box as qty FROM public.aw_master_product_rate UNION ALL
            SELECT product_code,barcode_pallet as barcode,(unit_rate_pallet*unit_rate_box) as qty FROM public.aw_master_product_rate
            )rt
            WHERE barcode=''$all''')
            as temp(product_code varchar(50),qty varchar(50))
            )as erpdb
            ");
            $qty = (int)($data[0]->qty);
            // dd($qty);
            $scanned = $product->scanned_qty + $qty;

            // dd($scanned);
            $product->update([
                'scanned_qty' => $scanned
            ]);
            $track = new Tracking();
            // $track->driver_info_id = 
            return response()->json(['doc_no'=>$doc_no,'bar_code'=>$product->bar_code,'data'=>$product,'scanned_qty'=>$qty],200);
            } catch (\Exception $e) {
                logger($e);
                return response()->json(['message'=>'Not found'],500);
            }
        }else{
            return response()->json(['message'=>'Not found'],404);
        }
    }

    public function confirm(Request $request)
    {
        $receive = GoodsReceive::where('id',$request->id)->first();
        $doc = Document::where('received_goods_id',$request->id)->get();
        $driver =  DriverInfo::where('received_goods_id',$request->id)
                            ->whereNull('duration')->first();

        $finish_driver = DriverInfo::where('received_goods_id',$request->id)
                            ->whereNotNull('duration')->get();

        if($driver)
        {
            $start = strtotime($driver->start_date.' '.$driver->start_time);
            $now    = Carbon::now()->timestamp;
            $diff = $now - $start;

            $data =  $this->repository->get_remain($request->id);

            $hour   = (int)($diff / 3600);
            $min    = (int)(($diff % 3600) / 60);
            $sec    = (int)(($diff % 3600) % 60);
            $pass   = sprintf('%02d:%02d:%02d', $hour, $min, $sec);
            $this_scanned = get_scanned_qty($request->id);
            $receive->update([
                'total_duration'        => get_all_duration($request->id),
                'remaining_qty'         => $data['remaining'],
                'exceed_qty'            => $data['exceed'],
                'status'                => 'incomplete'
            ]);

            $driver->update([
                'scanned_goods' => $this_scanned,
                'duration'      => $pass
            ]);


        }else{
            $receive->update([
                'total_duration' => '00:00:00',
                'status'         => 'incomplete'
            ]);
        }
        return response()->json(200);
    }

    // public  function edit_goods($id)
    // {
    //     $user_id =getAuth()->id;
    //     $data = GoodsReceive::where('id',$id)->first();

    //     return response()->json(200);
    // }

    public function car($id)
    {
        // $driver = DriverInfo::where('received_goods_id',$id)->get();
        $main   = GoodsReceive::where('id',$id)->first();
        $type = Truck::get();
        $source = Source::get();

        view()->share(['truck'=>$type,'source'=>$source]);
        return view('user.receive_goods.driver_info',compact('main'));
        // dd($driver);
    }

    public function finish_goods($id)
    {
        $receive = GoodsReceive::where('id',$id)->first();
        $driver = DriverInfo::where('received_goods_id',$id)
                            ->whereNull('duration')->first();

        $finish_driver = DriverInfo::where('received_goods_id',$id)
                                    ->whereNotNull('duration')->get();

        $start_time = strtotime($driver->start_date.' '.$driver->start_time);
        $now        = strtotime(Carbon::now()->format('Y-m-d H:i:s'));

        $data =  $this->repository->get_remain($id);
        $diff = $now - $start_time;
        $hour   = (int)($diff / 3600);
        $min    = (int)(($diff % 3600) / 60);
        $sec    = (int)(($diff % 3600) % 60);
        $time   = sprintf('%02d:%02d:%02d', $hour, $min, $sec);
        $this_scanned = get_scanned_qty($id);



        $receive->update([
            'total_duration'        => get_all_duration($id),
            'remaining_qty'         => $data['remaining'],
            'exceed_qty'            => $data['exceed'],
            'status'                => 'complete'
        ]);

        $driver->update([
            'scanned_goods' => $this_scanned,
            'duration'      => $time
        ]);

        return response()->json(200);
    }

    public function create_user()
    {
        $branch = Branch::get();
        $department = Department::get();
        view()->share(['branch'=>$branch,'department'=>$department]);
        return view('user.create_edit');
    }

    public function store_user(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name'          => 'required',
            'employee_code' => 'required',
            'password'      => 'required|confirmed',
            'password_confirmation'       => 'required|same:password',
            'department'    => 'required',
            'branch'        => 'required',
            'status'        => 'required'
        ]);
        // dd('yes');
        $user                   = new User();
        $user->name             = $request->name;
        $user->employee_code    = $request->employee_code;
        $user->password         = Hash::make($request->password);
        $user->password_str     = $request->password;
        $user->department_id    = $request->department;
        $user->branch_id        = $request->branch;
        $user->active           = $request->status == 'active' ? true : false;
        $user->role             = $request->role;
        $succ = $user->save();

        if($succ){
            return redirect()->route('user')->with('success','User Create Success');
        }else{
            return redirect()->route('user')->with('fails','User Create Fails');
        }
    }

    public function active_user(Request $request)
    {
        $active = $request->data == 1 ? true : false;
        $user = User::where('id',$request->id)->update([
            'active'    => $active
        ]);
        if($user)
        {
            return response()->json(200);
        }else{
            return response()->json(['fails'=>'fails'],500);
        }
    }

    public function del_user(Request $request)
    {
        // dd($request->all());
        $action = User::where('id',$request->id)->delete();
        if($action){
            return response()->json(200);
        }
    }

    public function edit_user($id)
    {
        $data = User::where('id',$id)->first();
        $branch = Branch::get();
        $department = Department::get();
        view()->share(['branch'=>$branch,'department'=>$department]);
        return view('user.create_edit',compact('data'));
    }

    public function update_user(Request $request)
    {
        $id = $request->id;
        $request->validate([
            'name'                      => 'required',
            'employee_code'             => "required|unique:users,employee_code,$id,id",
            'password'                  => 'required|confirmed',
            'password_confirmation'     => 'required|same:password',
            'department'                => 'required',
            'branch'                    => 'required',
            'status'                    => 'required',
        ]);

        if(getAuth()->role == 1)
        {
            User::where('id',$id)->update([
                'name'          => $request->name,
                'employee_code' => $request->employee_code,
                'password'      => Hash::make($request->password),
                'password_str'  => $request->password,
                'department_id' => $request->department,
                'branch_id'     => $request->branch,
                'active'     => $request->status == 'active' ? true : false,
                'role'     => $request->role
            ]);

            return redirect()->route('user')->with('success','User Update Success');
        }
    }

    public function del_doc(Request $request)
    {
        $doc = Document::where(['document_no'=>$request->data , 'received_goods_id' => $request->id])->first();
        $product = Product::where('document_id',$doc->id)->pluck('scanned_qty')->toArray();
        $zero = true;
        foreach($product as $item)
        {
            if($item > 0)
            {
                $zero = false;
                break;
            }
        }
        if($zero)
        {
            Product::where('document_id',$doc->id)->delete();
            $doc->delete();
            return response()->json(200);
        }else{
            return response()->json(['message'=>"You Cannot Remove"],404);
        }

        // $doc->delete();

    }

    public function driver_info($id)
    {
        $data = DriverInfo::where('id',$id)->first();
        return response()->json($data,200);
    }

    public function del_exceed(Request $request)
    {
        $product = Product::where('id',$request->id)->first();
        $product->update([
            'scanned_qty' => $product->qty
        ]);
        return response()->json(200);
    }
}
