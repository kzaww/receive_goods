<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\Log;
use Dompdf\Options;
use App\Models\User;
use App\Models\Branch;
use App\Models\CarGate;
use App\Models\Product;
use App\Models\Document;
use App\Models\Tracking;
use App\Models\DriverInfo;
use App\Models\RemoveTrack;
use App\Exports\DetailExcel;
use App\Exports\ReportExcel;
use App\Models\GoodsReceive;
use App\Models\ScanTrack;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\CssSelector\Node\FunctionNode;

class ReportController extends Controller
{
    public function product_list()
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('product_list');
        $log->action    = "go to product report page";
        $log->save();

        $product = [];

        if(request('search_data') && !request('search'))
        {
            return back()->with('error','Please Choose Search Method');
        }
        if(request('search') == 'main_no')
        {
            $main   = GoodsReceive::where('document_no',request('search_data'))->first();
            if($main)
            {
                $doc    = Document::where('received_goods_id',$main->id)->pluck('id');
                $product = Product::whereIn('document_id',$doc)->pluck('id');
            }
            // dd($main);
        }
        else if(request('search') == 'document_no')
        {
            $doc    = Document::where('document_no',request('search_data'))->first();
            if($doc){
                $product= Product::where('document_id',$doc->id)->pluck('id');
            }
        }
        $report = 'product';
        $url    = 'product_list';
        $product = Product::when(!request('search')  && !request('search_data') && !request('from_date') && !request('to_date'), function($q){
                                $q->whereDate('created_at',Carbon::today());
        })
                            ->when((request('search') == 'main_no' || request('search') == 'document_no') && request('search_data'),function($q) use($product){
                                $q->whereIn('id',$product);
                            })
                            ->when(request('search') == 'product_code' && request('search_data'),function($q){
                                $q->where('bar_code',request('search_data'));
                            })
                            ->when(request('from_date'),function($q){
                                $q->whereDate('created_at','>=',request('from_date'));
                            })
                            ->when(request('to_date'),function($q){
                                $q->whereDate('created_at','<=',request('to_date'));
                            })
                            ->paginate(15);
        return view('user.report.report',compact('report','product','url'));
    }

    public function finished_documents()
    {
            $log            = new Log();
            $log->user_id   = getAuth()->id;
            $log->history   = route('finished_documents');
            $log->action    = "go to finished documents report page";
            $log->save();

        $report = 'finish';
        $url    = 'finished_documents';

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
                            ->when(!request('search') && !request('search_data') && !request('branch') && !request('status') && !request('from_date') && !request('to_date') , function($q){
                                $q->whereDate('created_at',Carbon::today());
            })
                            ->whereNotNull('total_duration')
                            ->where('status','complete')
                            ->orderBy('created_at','desc')
                            ->paginate(15);

        $branch = Branch::get();
        return view('user.report.report',compact('report','data','branch','url'));
    }

    public function truck_list()
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('truck_list');
        $log->action    = "go to Truck report page";
        $log->save();

        $report = 'truck';
        $url    = 'truck_list';
        if(request('search') && !request('search_data'))
        {
            return back()->with('error','Please add search data');
        }else if(!request('search') && request('search_data')){
            return back()->with('error','Please add search method');
        }

        $truck = [];
        if(request('search') == 'main_no' && request('search_data'))
        {
            $main = GoodsReceive::where('document_no',request('search'))->first();
            if($main)
            {
                $truck= DriverInfo::where('received_goods_id',$main->id)->pluck('id');
            }
        }elseif(request('search') == 'product_code' && request('search_data'))
        {

            $product = Product::where('bar_code',request('search_data'))->pluck('id');
            if($product)
            {
                $truck   = Tracking::whereIn('product_id',$product)->pluck('driver_info_id');
            }
        }elseif((request('search') == 'truck_no' || request('search') == 'driver_name') && request('search_data'))
        {
            $truck = DriverInfo::where(request('search'),request('search_data'))->pluck('id');
        }


        $truck  = Driverinfo::when(!request('search') && !request('search_data') && !request('gate') && !request('from_date') && !request('to_date') , function($q){
                                $q->whereDate('created_at',Carbon::today());
                        })
                            ->when((request('search') == 'main_no' || request('search') == 'product_code' || request('search') == 'truck_no' || request('search') == 'driver_name') && request('search_data'),function($q) use($truck){
                                $q-> whereIn('id', $truck);
                        })
                            ->when(request('gate'),function($q){
                                $q-> where('gate',request('gate'));
                            })
                            ->when(request('from_date'),function($q){
                                $q->whereDate('created_at', '>=', request('from_date'));
                            })
                            ->when(request('to_date'),function($q){
                                $q->whereDate('created_at','<=',request('to_date'));
                            })
                            ->paginate(15);

        $branch = Branch::get();
        $gate   = CarGate::get();
        return view('user.report.report',compact('report','truck','branch','gate','url'));
    }

    public function remove_list()
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('remove_list');
        $log->action    = "go to Remove Lists report page";
        $log->save();

        $report = 'remove';
        $url = 'remove_list';

        if(request('search') && !request('search_data'))
        {
            return back()->with('error','Please add search data');
        }else if(!request('search') && request('search_data')){
            return back()->with('error','Please add search method');
        }
        $no = [];
        $product = '';
        $user    = '';
        if(request('search') == 'main_no' && request('search_data'))
        {
            $document   = GoodsReceive::where('document_no',request('search_data'))->first();
            if($document)
            {
                $no       = RemoveTrack::where('received_goods_id',$document->id)->pluck('id');
            }
        }elseif(request('search') == 'product_code' && request('search_data'))
        {
            $product    = Product::where('bar_code',request('search_data'))->pluck('id');
        }elseif(request('search') == 'user' && request('search_data'))
        {
            $user   = User::where('name',request('search_data'))->first();
        }

        $data = RemoveTrack::when(!request('search') && !request('from_date') && !request('to_date'),function($q)
                            {
                                $q->whereDate('created_at',Carbon::today());
                            })
                            ->when(request('search') == 'main_no' && request('search_data') , function($q) use($no){
                                $q->whereIn('id',$no);
                            })
                            ->when(request('search') == 'product_code' && request('search_data'),function($q) use($product){
                                $q->where('proudct_id',$product);
                            })
                            ->when(request('search') == 'user' && request('search_data'),function($q) use($user){
                                $q->where('user_id',$user);
                            })
                            ->when(request('from_date'),function($q){
                                $q->whereDate('created_at', '>=', request('from_date'));
                            })
                            ->when(request('to_date'),function($q){
                                $q->whereDate('created_at','<=',request('to_date'));
                            })
                            ->paginate(15);

        return view('user.report.report',compact('data','report','url'));
    }

    public function po_to_list()
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('po_to_list');
        $log->action    = "go to PO/TO Document report page";
        $log->save();

        $report = 'po_to';
        $url = 'po_to_list';

        if(request('search') && !request('search_data'))
        {
            return back()->with('error','Please add search data');
        }else if(!request('search') && request('search_data')){
            return back()->with('error','Please add search method');
        }

        $no = [];
        $doc = [];

        if(request('search') == 'main_no' && request('search_data'))
        {
            $document   = GoodsReceive::where('document_no',request('search_data'))->first();
            if($document)
            {
                $no       = Document::where('received_goods_id',$document->id)->pluck('id');
            }
        }elseif(request('search') == 'product_code' && request('search_data'))
        {
            $id = Product::where('bar_code',request('search_data'))->first();
            $doc = Document::where('id',$id->document_id)->first();
        }

        $docs = Document::when(!request('search') && !request('from_date') && !request('to_date'),function($q)
                        {
                            $q->whereDate('created_at',Carbon::today());
                        })
                        ->when(request('search') == 'main_no' && request('search_data'),function($q) use($no) {

                            $q->whereIn('id',$no);
                        })
                        ->when(request('search') == 'product_code' && request('search_data'),function($q) use($doc) {

                            $q->where('id' , $doc->id);
                        })
                        ->when(request('search') == 'document_no' && request('search_data'),function($q) {

                            $q->where('document_no' , request('search_data'));
                        })
                        ->when(request('from_date'),function($q){
                            $q->whereDate('created_at', '>=', request('from_date'));
                        })
                        ->when(request('to_date'),function($q){
                            $q->whereDate('created_at','<=',request('to_date'));
                        })
                        ->paginate(15);
        return view('user.report.report',compact('docs','report','url'));
    }

    public function shortage_list()
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('shortage_list');
        $log->action    = "go to Shortage Product report page";
        $log->save();
        $report = 'shortage';
        $url = 'shortage_list';

        if(request('search') && !request('search_data'))
        {
            return back()->with('error','Please add search data');
        }else if(!request('search') && request('search_data')){
            return back()->with('error','Please add search method');
        }

        $pd_ids = [];
        if(request('search') == 'main_no' && request('search_data'))
        {
            $document   = GoodsReceive::where('document_no',request('search_data'))->where('status','complete')->first();
            if($document)
            {
                $no         = Document::where('received_goods_id',$document->id)->pluck('id');
                $pd_ids     = Product::whereIn('document_id',$no)->where(DB::raw('qty'),'>',DB::raw('scanned_qty'))->pluck('id');
            }
        }elseif(request('search') == 'document_no' && request('search_data'))
        {
            $doc = Document::where('document_no',request('search_data'))->first();
            $pd_ids = Product::where('document_id',$doc->id)->pluck('id');
        }


        $reg_ids        = GoodsReceive::where('status','complete')->pluck('id');
        $document_ids   = Document::whereIn('received_goods_id',$reg_ids)->pluck('id');
        $data   = Product::when(!request('search') && !request('from_date') && !request('action') && !request('to_date'),function($q)
                        {
                            $q->whereDate('created_at',Carbon::today());
                        })
                        ->when(request('search') != 'product_code' && request('search_data'),function($q) use($pd_ids) {

                            $q->whereIn('id',$pd_ids);
                        })
                        ->when(request('search') == 'product_code' && request('search_data'),function($q){

                            $q->where('bar_code' , request('search_data'));
                        })
                        ->when(request('action') == 'excess',function($q){

                            $q->where(DB::raw("qty"),'<',DB::raw('scanned_qty'));
                        })
                        ->when(request('action') == 'shortage',function($q){

                            $q->where(DB::raw("qty"),'>',DB::raw('scanned_qty'));
                        })
                            ->whereIn('document_id',$document_ids)
                            ->where(DB::raw("qty"),'!=',DB::raw('scanned_qty'))
                            ->paginate(15);

        return view('user.report.report',compact('data','report','url'));
    }


    public function excel_export(Request $request)
    {

        $date = Carbon::now()->format('Ymd');
        $search = $request->except('_token');
        switch ($request->report)
        {
            case  'product'     :$report = 'totalpd';break;
            case  'finish'      :$report = 'finishdoc';break;
            case  'truck'       :$report = 'totaltruck';break;
            case  'remove'      :$report = 'removepd';break;
            case  'po_to'       :$report = 'potolist';break;
            case  'shortage'    :$report = 'shortage';break;
            default             :$report = '';break;
        }
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('excel_export');
        $log->action    = "$report report excel export";
        $log->save();
        return Excel::download(new ReportExcel($search), $report . 'report' . $date . '.xlsx');
    }

    public function detail_excel_export($id,$action)
    {
        $date = Carbon::now()->format('Ymd');
        // $action =

        // $driver     = DriverInfo::where('id',$id)->first();
        // $reg        = GoodsReceive::where('id',$driver->received_goods_id)->first();
        // $document   = [];
        // $track      = Tracking::where('driver_info_id', $id)->get();
        // foreach($track as $item)
        // {
        //     if(!in_array($item->product->doc->id,$document))
        //     {
        //         $document[] = $item->product->doc->id;
        //     }
        // }

        // return view('user.report.detail_excel_report',compact('driver','reg','document','track'));
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('detail_excel_export',['id'=>$id,'action'=>$action]);
        $log->action    = "$action detail excel export";
        $log->save();
        if($action == 'truck')
        {
            $truck = DriverInfo::where('id',$id)->first();
            $truck_no = $truck->truck_no;
            return Excel::download(new DetailExcel($id,$action),"$truck_no$date.xlsx");
        }elseif($action == 'document')
        {
            $document   = Document::where('id',$id)->first();
            $doc_no     = $document->document_no;
            return Excel::download(new DetailExcel($id,$action),"$doc_no$date.xlsx");
        }elseif($action == 'doc')
        {
            $document   = GoodsReceive::find($id);
            $doc_no     = $document->document_no;
            return Excel::download(new DetailExcel($id,$action),"$doc_no$date.xlsx");
        }elseif($action == 'scan')
        {
            $driver     = DriverInfo::where('id',$id)->first();
            $truck_no   = $driver->truck_no;
            return Excel::download(new DetailExcel($id,$action),$truck_no . 'scan' . $date . '.xlsx');
        }
    }

    public function product_pdf($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('product_pdf',['id'=>$id]);
        $log->action    = "PDF Generate for Product";
        $log->save();

        $docs = Document::where('received_goods_id',$id)->pluck('id');
        $data = Product::whereIn('document_id',$docs)->get();
        $doc_no = GoodsReceive::where('id',$id)->first();
        $date = Carbon::now()->format('Ymd');
        view()->share(['data'=>$data]);

        $pdf = PDF::loadView('user.exports.product_pdf', compact('data'));
        return $pdf->stream("$doc_no->document_no.$date.pdf");
    }

    public function truck_detail_pdf($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('truck_detail_pdf',['id'=>$id]);
        $log->action    = "PDF Generate for Truck Detail";
        $log->save();

        $action = 'print';
        $detail = 'truck';
        $date = Carbon::now()->format('Ymd');
        $driver     = DriverInfo::where('id',$id)->first();
        $reg        = GoodsReceive::where('id',$driver->received_goods_id)->first();
        $document   = [];
        $track      = Tracking::where('driver_info_id', $id)->get();
        $scan_track = ScanTrack::where('driver_info_id',$id)->sum('count');
        foreach($track as $item)
        {
            if(!in_array($item->product->doc->id,$document))
            {
                $document[] = $item->product->doc->id;
            }
        }

        $pdf = PDF::loadView('user.report.detail_excel_report', compact('driver','reg','document','track','action','detail','scan_track'));
        return $pdf->stream("$driver->truck_no.$date.pdf");
    }

    public function document_detail_pdf($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('document_detail_pdf',['id'=>$id]);
        $log->action    = "PDF Generate for Document(REG) Detail";
        $log->save();

        $action = 'print';
        $date = Carbon::now()->format('Ymd');
        $detail     = 'document';
        $document   = Document::where('id',$id)->first();
        $reg        = GoodsReceive::where('id',$document->received_goods_id)->first();
        $product    = Product::where('document_id',$id)->get();
        $pd_id      = Product::where('document_id',$id)->pluck('id');
        $track      = Tracking::whereIn('product_id',$pd_id);
        $truck      = $track->distinct()->pluck('driver_info_id');
        $truck      = DriverInfo::whereIn('id',$truck)->get();
        $track      = $track->get();
        $document_no= $document->document_no;
        $pdf = PDF::loadView('user.report.detail_excel_report', compact('detail','truck','document','product','track','reg','action'));
        return $pdf->stream("$document_no$date.pdf");
    }

    public function doc_detail_pdf($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('doc_detail_pdf',['id'=>$id]);
        $log->action    = "PDF Generate for Document(PO/TO) Detail";
        $log->save();

        $action     = 'print';
        $date = Carbon::now()->format('Ymd');
        $detail     = 'doc';
        $reg        = GoodsReceive::where('id',$id)->first();
        $document   = Document::where('received_goods_id',$id)->get();
        $driver     = DriverInfo::where('received_goods_id',$id)->get();
        $pdf        = PDF::loadView('user.report.detail_excel_report', compact('detail','driver','document','reg','action'))->setPaper('a4', 'landscape');
        $doc_no    = $reg->document_no;
        return $pdf->stream("$doc_no$date.pdf");
    }

    public function scan_count_pdf($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('doc_detail_pdf',['id'=>$id]);
        $log->action    = "PDF Generate for Document(PO/TO) Detail";
        $log->save();

        $action     = 'print';
        $date = Carbon::now()->format('Ymd');
        $detail = 'scan';
        $scan_track = ScanTrack::where('driver_info_id',$id)->orderBy('id')->get();
        $pdf        = PDF::loadView('user.report.detail_excel_report', compact('detail','scan_track','action'));
        $truck_no    = $scan_track[0]->driver->truck_no;
        return $pdf->stream("$truck_no$date.pdf");
    }

    public function detail_doc($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('detail_doc',['id'=>$id]);
        $log->action    = "Go To Document(PO/TO) Detail Page";
        $log->save();

        $detail     = 'doc';
        $reg        = GoodsReceive::where('id',$id)->first();
        $document   = Document::where('received_goods_id',$id)->get();
        $driver     = DriverInfo::where('received_goods_id',$id)->get();
        return view('user.report.detail_report',compact('reg','document','driver','detail'));
    }

    public function detail_truck($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('detail_truck',['id'=>$id]);
        $log->action    = "Go To Truck Detail Page";
        $log->save();

        $detail     = 'truck';
        $driver     = DriverInfo::where('id',$id)->first();
        $reg        = GoodsReceive::where('id',$driver->received_goods_id)->first();
        $document   = [];
        $track      = Tracking::where('driver_info_id', $id)->get();
        $scan_track = ScanTrack::where('driver_info_id',$id)->sum('count');
        foreach($track as $item)
        {
            if(!in_array($item->product->doc->id,$document))
            {
                $document[] = $item->product->doc->id;
            }
        }
        return view('user.report.detail_report',compact('reg','driver','document','detail','track','scan_track'));
    }

    public function detail_document($id)
    {
        $log            = new Log();
        $log->user_id   = getAuth()->id;
        $log->history   = route('detail_document',['id'=>$id]);
        $log->action    = "Go To Document(REG) Detail Page";
        $log->save();

        $detail     = 'document';
        $document   = Document::where('id',$id)->first();
        $reg        = GoodsReceive::where('id',$document->received_goods_id)->first();
        $product    = Product::where('document_id',$id)->get();
        $pd_id      = Product::where('document_id',$id)->pluck('id');
        $track      = Tracking::whereIn('product_id',$pd_id);
        $truck      = $track->distinct()->pluck('driver_info_id');
        $truck      = DriverInfo::whereIn('id',$truck)->get();
        $track      = $track->get();
        return view('user.report.detail_report',compact('detail','truck','document','product','track','reg'));
    }

    public function Scan_count($id)
    {
        $detail     = 'scan';
        $scan_track = ScanTrack::where('driver_info_id',$id)->orderBy('id')->get();
        return view('user.report.detail_report',compact('detail','scan_track'));
    }
}
