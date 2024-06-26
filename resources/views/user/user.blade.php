@extends('layout.layout')

@section('content')
<div class="error_msg">
    @if (Session::has('fails'))
    <div class="m-5 text-rose-500 bg-rose-200 ps-5 border-l-4 border-rose-500 py-2">{{ Session::get('fails') }}</div>
@endif
@if (Session::has('success'))
    <div class="m-5 text-emerald-500 bg-emerald-200 ps-5 border-l-4 border-emerald-500 py-2">{{ Session::get('success') }}</div>
@endif

</div>
    <div class="m-5">
        <div class="ms-1 flex justify-between">
            <span class="text-2xl font-serif tracking-wide">@switch($type)
                @case('user')
                Users Lists
                    @break
                @case('role')
                Roles Lists
                @break
                @case('permission')
                Permissions Lists
                @break
                @case('gate')
                Gate Lists
                @break
                @case('car_type')
                Car Type Lists
                @break
                @default
                Users Lists
            @endswitch</span>
            <?php
                switch ($type) {
                    case 'user':
                        $url = 'user/create';
                        break;
                    case 'role':
                        $url = 'role/create';
                        break;
                    case 'permission':
                        $url = 'permission/create';
                        break;
                    case 'gate':
                        $url = 'gate/create';
                        break;
                    case 'car_type':
                        $url = 'car_type/create';
                        break;
                    default:
                        $url = url()->current();
                        break;
                }
            ?>
            <button class="bg-emerald-400 px-2 py-1 rounded-md mr-2 hover:bg-emerald-600" onclick="javascirpt:window.location.href = '{{ $url }}'"><i class='bx {{ $type == 'user' ? 'bx-user-plus' : 'bx-list-plus'  }} text-white text-xl ms-1' ></i></button>
        </div>
        @if ($type == 'user')
            <form action="{{ route('user') }}" method="Get">
                <div class="grid grid-cols-7 gap-4">
                        <div class="flex flex-col">
                            <label for="branch">Choose Branch :</label>
                            <Select name="branch" id="branch" class="h-10 mt-3 rounded-t-lg px-3 shadow-md focus:outline-none focus:border-0 focus:ring-2 focus:ring-offset-2" style="appearance: none;">
                                <option value="">Choose Branch</option>
                                @foreach ($branch as $item)
                                    <option value="{{ $item->id }}" {{ request('branch') == $item->id ? 'selected' : '' }}>{{ $item->branch_name }}</option>
                                @endforeach
                            </Select>
                        </div>

                        <div class="flex flex-col">
                            <label for="search_data">User Name / User Code :</label>
                            <input type="text" name="search_data" id="search_data" class="px-4 w-[80%] h-10 border border-slate-400 rounded-md mt-3 focus:outline-none focus:ring-2 focus:ring-offset-2" value="{{ request('search_data') ?? '' }}">
                        </div>

                    <div class="">
                        <button class="bg-amber-400 h-10 w-[40%] rounded-lg ms-4 mt-9 hover:bg-amber-600 hover:text-white">Search</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="">
            <table class="w-full mt-4">
                <thead>
                    @if ($type == 'user')
                        <tr class="">
                            <th class="py-2 bg-slate-400  rounded-tl-md w-10"></th>
                            <th class="py-2 bg-slate-400 border">User's Name</th>
                            <th class="py-2 bg-slate-400 border">User's Employee Code</th>
                            <th class="py-2 bg-slate-400 border">User's Branch</th>
                            <th class="py-2 bg-slate-400 border">User's Role</th>
                            <th class="py-2 bg-slate-400 border">User's Status</th>
                            <th class="py-2 bg-slate-400  rounded-tr-md">Action</th>
                        </tr>
                    @elseif ($type == 'role')
                        <tr class="">
                            <th class="py-2 bg-slate-400  rounded-tl-md w-10"></th>
                            <th class="py-2 bg-slate-400 border">Name</th>
                            <th class="py-2 bg-slate-400  rounded-tr-md">Action</th>
                        </tr>
                    @elseif ($type == 'permission')
                        <tr class="">
                            <th class="py-2 bg-slate-400  rounded-tl-md w-10"></th>
                            <th class="py-2 bg-slate-400 border">Name</th>
                            <th class="py-2 bg-slate-400  rounded-tr-md">Action</th>
                        </tr>
                    @elseif ($type == 'gate')
                        <tr>
                            <th class="py-2 bg-slate-400  rounded-tl-md w-10"></th>
                            <th class="py-2 bg-slate-400 border">Gate Name</th>
                            <th class="py-2 bg-slate-400 border">Branch</th>
                            <th class="py-2 bg-slate-400  rounded-tr-md">Action</th>
                        </tr>
                    @elseif ($type == 'car_type')
                        <tr>
                            <th class="py-2 bg-slate-400  rounded-tl-md w-10"></th>
                            <th class="py-2 bg-slate-400 border">Car Name</th>
                            <th class="py-2 bg-slate-400  rounded-tr-md">Action</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    <input type="hidden" id="type" value="{{ $type }}">
                    @if ($type == 'user')
                        @foreach ($data as $item)
                            <tr>
                                <td class="h-10 text-center border border-slate-400">{{ $data->firstItem()+$loop->index  }}</td>
                                <td class="h-10 text-center border border-slate-400">{{ $item->name }}</td>
                                <td class="h-10 text-center border border-slate-400">{{ $item->employee_code }}</td>
                                @if(count($item->user_branches) > 0)
                                    <td class="h-10 text-center border border-slate-400">
                                    @foreach($item->user_branches as $index => $tem)
                                    {{ $tem->branch->branch_name.(count($item->user_branches)-1 == $index ? '' : ',') }}
                                    @endforeach
                                    
                                    </td>
                                @else 
                                    <td class="h-10 text-center border border-slate-400">{{ $item->branch->branch_name }}</td>
                                @endif
                                <td class="h-10 text-center border border-slate-400">{{ $item->roleName() }}</td>
                                <td class="h-10 text-center border border-slate-400 {{ $item->status == 1 ? 'text-emerald-600' : 'text-rose-600' }} ">
                                    @if($item->role != 1)
                                    <span class="user_status">
                                        {{ $item->status == 1 ? 'Active' : 'Inactive' }}
                                    </span>

                                        <label class="relative inline-flex items-center cursor-pointer translate-y-1 ms-5">
                                            <input type="checkbox" value="{{ $item->id }}" class="sr-only peer user_active" {{ $item->status == 1 ? 'checked' : '' }}>
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                        </label>
                                        @endif
                                </td>
                                <td class="h-10 text-center border border-slate-400 ">
                                    @if ($item->role != 1)
                                    <button class="bg-sky-500 hover:bg-sky-700 px-1 rounded-md mr-1" onclick="window.location.href = 'user/edit/{{ $item->id }}'"><i class='bx bxs-edit text-white mt-1' ></i></button>
                                    <button class="bg-rose-500 hover:bg-rose-700 px-1 rounded-md mr-1 del_btn" data-id="{{ $item->id }}"><i class='bx bxs-trash-alt text-white mt-1'></i></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @elseif ($type == 'role')
                        @foreach ($data as $item)
                        <tr>
                            <td class="h-10 text-center border border-slate-400">{{ $data->firstItem()+$loop->index  }}</td>
                            <td class="h-10 text-center border border-slate-400">{{ $item->name }}</td>
                            <td class="h-10 text-center border border-slate-400 ">
                                <button class="bg-sky-500 hover:bg-sky-700 px-1 rounded-md mr-1" onclick="window.location.href = 'role/edit/{{ $item->id }}'"><i class='bx bxs-edit text-white mt-1' ></i></button>
                                <button class="bg-rose-500 hover:bg-rose-700 px-1 rounded-md mr-1 del_btn" data-id="{{ $item->id }}"><i class='bx bxs-trash-alt text-white mt-1'></i></button>
                            </td>
                        </tr>
                    @endforeach
                    @elseif ($type == 'permission')
                        @foreach ($data as $item)
                        <tr>
                            <td class="h-10 text-center border border-slate-400">{{ $data->firstItem()+$loop->index  }}</td>
                            <td class="h-10 text-center border border-slate-400">{{ $item->name }}</td>
                            <td class="h-10 text-center border border-slate-400 ">
                                <button class="bg-amber-500 hover:bg-amber-700 px-1 rounded-md mr-1" title="detail" onclick="window.location.href = 'view_permission/{{ $item->id }}'"><i class='bx bxs-info-circle text-white mt-1' ></i></button>
                            </td>
                        </tr>
                    @endforeach
                    @elseif ($type == 'gate')
                        @foreach ($data as $item)
                            <tr>
                                <td class="h-10 text-center border border-slate-400">{{ $data->firstItem()+$loop->index  }}</td>
                                <td class="h-10 text-center border border-slate-400">{{ $item->name }}</td>
                                <td class="h-10 text-center border border-slate-400">{{ $item->branches->branch_name }}</td>
                                <td class="h-10 text-center border border-slate-400 ">
                                    <button class="bg-sky-500 hover:bg-sky-700 px-1 rounded-md mr-1" onclick="window.location.href = 'gate/edit/{{ $item->id }}'"><i class='bx bxs-edit text-white mt-1' ></i></button>
                                    <button class="bg-rose-500 hover:bg-rose-700 px-1 rounded-md mr-1 del_btn" data-id="{{ $item->id }}"><i class='bx bxs-trash-alt text-white mt-1'></i></button>
                                </td>
                            </tr>
                        @endforeach
                    @elseif ($type == 'car_type')
                            @foreach ($data as $item)
                                <tr>
                                    <td class="h-10 text-center border border-slate-400">{{ $data->firstItem()+$loop->index  }}</td>
                                    <td class="h-10 text-center border border-slate-400">{{ $item->truck_name }}</td>
                                    <td class="h-10 text-center border border-slate-400 ">
                                        <button class="bg-sky-500 hover:bg-sky-700 px-1 rounded-md mr-1" onclick="window.location.href = 'car_type/edit/{{ $item->id }}'"><i class='bx bxs-edit text-white mt-1' ></i></button>
                                        <button class="bg-rose-500 hover:bg-rose-700 px-1 rounded-md mr-1 del_btn" data-id="{{ $item->id }}"><i class='bx bxs-trash-alt text-white mt-1'></i></button>
                                    </td>
                                </tr>
                            @endforeach
                    @endif

                </tbody>
            </table>
        </div>
        {{-- @if (request('search') || request('search_data') || request('branch') || request('status') || request('from_date') || request('to_date'))
        <div class="mt-2">
            <button class="bg-sky-600 text-white px-3 py-2 rounded-md" onclick="javascirpt:window.location.href = 'list'">Back to Default</button>
        </div>
        @endif --}}
        <div class="flex justify-center text-xs mt-2 bg-white mt-6">
            {{ $data->appends(request()->query())->links() }}

    </div>
    </div>

    @push('js')
        <script>
            $(document).ready(function(){

                var token = $("meta[name='__token']").attr('content');

                $(document).on('click','.user_active',function(e){
                    $this = $(this);
                    $id = $this.val();
                    if($(this).prop('checked') == true){
                        $data = 1;
                    }else{

                        $data = 0;
                    }
                    $.ajax({
                        url : "{{ route('active_user') }}",
                        type: "POST",
                        data: {_token : token , data : $data , id : $id},
                        success : function(res){
                            if($data == 0)
                            {
                                $this.prop('checked',false);
                                $this.parent().parent().find('.user_status').text('Inactive');
                                $this.parent().parent().removeClass('text-emerald-600 text-rose-600');
                                $this.parent().parent().addClass('text-rose-600');
                            }else{
                                $this.prop('checked',true);
                                $this.parent().parent().find('.user_status').text('Active');
                                $this.parent().parent().removeClass('text-emerald-600 text-rose-600');
                                $this.parent().parent().addClass('text-emerald-600');
                            }
                        }
                    })
                })

                $(document).on('click','.del_btn',function(e){
                   $id = $(this).data('id');
                    $this = $(this);
                    $type = $('#type').val();
                    $url = "{{ route('del') }}";

                   Swal.fire({
                    icon : 'info',
                    title: 'Are You Sure?',
                    showCancelButton:true,
                    confirmButtonText:'Yes',
                    cancelButtonText: "No",
                   }).then((result)=>{
                    if(result.isConfirmed){
                        $.ajax({
                            url : $url,
                            type: 'post',
                            data: {_token:token , id : $id ,type : $type},
                            success: function(res){
                                $this.parent().parent().remove();
                                $('.error_msg').append(`
                                <div class="m-5 text-emerald-500 bg-emerald-200 ps-5 border-l-4 border-emerald-500 py-2">Delete Success</div>
                                `);
                            },
                            error: function(xhr,status,error)
                            {
                                $('.error_msg').append(`
                                <div class="m-5 text-rose-500 bg-rose-200 ps-5 border-l-4 border-rose-500 py-2">Delete Fails</div>
                                `);
                            }
                        })
                    }
                   })
                })
            })
        </script>
    @endpush
@endsection
