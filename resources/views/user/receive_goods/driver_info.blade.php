@extends('layout.layout')

@section('content')
<div class="px-20 mt-20">
    @if (session('fails'))
    <div class="my-4 bg-rose-200 h-10 font-medium text-lg ps-5 pt-1 rounded-lg text-red-600" style="width:99%">
        {{ session('fails') }}
    </div>
    @endif
        <fieldset class="mt-3 border border-slate-500 rounded-md p-5">
            <legend class="px-4 text-2xl font-serif"> Driver Info </legend>

            @if (isset($driver))
                <div class="text-center">
                    <select id="old_driver" class="px-3 min-w-[20%] h-10 rounded-t-lg mt-3 px-3 shadow-md focus:outline-none focus:border-0 focus:ring-2 focus:ring-offset-2" style="appearance: none;">
                        <option value="">Choose Previous Car</option>
                        @foreach($driver as $item)
                            <option value="{{ $item->id }}">{{ $item->truck_no }}</option>
                        @endforeach

                    </select>
                </div>
            @endif

            <form action="{{ route('store_car_info') }}" method="POST">
                @csrf
                <input type="hidden" name="{{ isset($main) ? 'main_id' : '' }}" value="{{ isset($main) ? $main->id : ''  }}">
                <div class="grid grid-cols-2 gap-5 my-5">
                    <div class="flex flex-col px-10">
                        <label for="driver_name">Driver Name<span class="text-rose-600">*</span> :</label>
                        <input type="text" name="driver_name" id="driver_name" class="mt-3 border-2 border-slate-600 rounded-lg ps-5 py-2 focus:border-b-4 focus:outline-none" placeholder="name..." value="{{ old('driver_name') }}">
                        @error('driver_name')
                            <small class="text-rose-500 ms-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="flex flex-col px-10">
                        <label for="driver_phone">Driver Phone<span class="text-rose-600">*</span> :</label>
                        <input type="number" name="driver_phone" id="driver_phone" class="mt-3 border-2 border-slate-600 rounded-lg ps-5 py-2 focus:border-b-4 focus:outline-none" value="{{ old('driver_phone') }}" placeholder="09*********">
                        @error('driver_phone')
                        <small class="text-rose-500 ms-1">{{ $message }}</small>
                    @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5 my-5">
                    <div class="flex flex-col px-10">
                        <label for="driver_nrc">Driver NRC<span class="text-rose-600">*</span> :</label>
                        <input type="text" name="driver_nrc" id="driver_nrc" class="mt-3 border-2 border-slate-600 rounded-lg ps-5 py-2 focus:border-b-4 focus:outline-none" value="{{ old('driver_nrc') }}" placeholder="nrc...">
                        @error('driver_nrc')
                        <small class="text-rose-500 ms-1">{{ $message }}</small>
                    @enderror
                    </div>

                    <div class="flex flex-col px-10">
                        <label for="truck_no">Truck No<span class="text-rose-600">*</span> :</label>
                        <input type="text" name="truck_no" id="truck_no" class="mt-3 border-2 border-slate-600 rounded-lg ps-5 py-2 focus:border-b-4 focus:outline-none" value="{{ old('truck_no') }}" placeholder="truck...">
                        @error('truck_no')
                        <small class="text-rose-500 ms-1">{{ $message }}</small>
                    @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-5 my-5">
                    <div class="flex flex-col px-10">
                        <label for="truck_type">Type of Truck<span class="text-rose-600">*</span> :</label>
                        <Select name="truck_type" id="truck_type" class="h-10 rounded-t-lg mt-3 px-3 shadow-md focus:outline-none focus:border-0 focus:ring-2 focus:ring-offset-2" style="appearance: none;">
                            <option value="">Choose Type of Truck</option>
                            @foreach ($truck as $item)
                                <option value="{{ $item->id }}" {{ old('truck_type') == $item->id ? 'selected' : '' }}>{{ $item->truck_name }}</option>
                            @endforeach
                        </Select>
                        @error('truck_type')
                        <small class="text-rose-500 ms-1">{{ $message }}</small>
                    @enderror
                    </div>
                    @if (!isset($main))

                        <div class="flex flex-col px-10">
                            <label for="source">Source<span class="text-rose-600">*</span> :</label>
                            <Select name="source" id="source" class="h-10 rounded-t-lg mt-3 px-3 shadow-md focus:outline-none focus:border-0 focus:ring-2 focus:ring-offset-2" style="appearance: none;">
                                <option value="">Choose Source</option>
                                @foreach ($source as $item)
                                    <option value="{{ $item->id }}" {{ old('source') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                @endforeach
                            </Select>
                            @error('source')
                            <small class="text-rose-500 ms-1">{{ $message }}</small>
                        @enderror
                        </div>

                    @endif
                </div>

                <div class="grid grid-cols-2 gap-5 my-5">

                    <div class="">

                    </div>
                    <div class="">
                        <button type="submit" class="bg-emerald-400 text-white px-10 py-2 rounded-md float-end mt-7 mr-10">Save</button>
                    </div>
                </div>
            </form>
        </fieldset>
    </div>

    @push('js')
        <script>
            $(document).ready(function(){
                $(document).on('keypress','#driver_phone',function(e){
                    let filter = true;

                    if($(this).val().length < 11){
                        if  ( e.keyCode >=48 && e.keyCode <= 57){
                            filter = true;
                        }else{
                            filter = false;
                        }
                    }else{
                        filter = false;
                    }

                    if(!filter){
                        e.preventDefault();
                    }
                })


                $(document).on('change','#old_driver',function(e){
                    $val = $(this).val();
                    // $('#truck_type option').each((i,v)=>{
                    //     if(i == 0){
                    //         console.log($(v).eq(i).text());

                    //     }
                    //         });
                    //         return;
                    $.ajax({
                        url : "/get_driver_info/"+$val,
                        type: 'GET',
                        success: function(res){
                            $('#driver_name').val(res.driver_name);
                            $('#driver_phone').val(res.ph_no);
                            $('#driver_nrc').val(res.nrc_no);
                            $('#truck_no').val(res.truck_no);
                            $('#truck_type option').each((i,v)=>{
                                $(v).attr('selected',false);
                                if($(v).val() == res.type_truck)
                                {
                                    $(v).prop('selected',true);
                                }
                            });

                        }
                    })
                })
            })
        </script>
    @endpush
@endsection
