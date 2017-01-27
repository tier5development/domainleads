<?php
namespace App\Http\Controllers;
use Input;
use Auth;
use App\Jobs\validatephone;
use Illuminate\Http\Request;
use \App\LeadUser;
use \App\Lead;
use \App\Domain;
use DB;

use App\User;

use Excel;

class DomainLeadsController extends Controller
{


   public function myleads()
   {
        $id = Auth::user()->id;
        $leads = LeadUser::where('user_id' , $id)->get();
        
        $i=0;
        foreach($leads as $l)
        {
          
          $myleads[$i++] = Domain::where('id' , $l->domain_id )->first();
          
        }

      return view('user_session.myleads' , compact('myleads'));
  }

  public function downloadExcel(Request $request)

  {
    $type='xlsx'; 
      
      $user_type=Auth::user()->user_type;
      $user_id=Auth::user()->id;
        $domains_for_export_allChecked=$request->domains_for_export_allChecked;
        if($domains_for_export_allChecked==1){
          if($user_type==1){
            $exel_data=DB::table('leadusers')
                          ->join('leads', 'leads.id', '=', 'leadusers.user_id')
                          ->join('domains', 'leads.id', '=', 'domains.user_id')
                          ->join('validatephone', 'validatephone.user_id', '=', 'leads.id')
                          ->select('leads.registrant_name as name','domains.domain_name as website','leads.registrant_address as address','leads.registrant_phone as phone','leads.registrant_email as email_id')
                          ->where('leadusers.user_id',$user_id)->get();  
          }else{
             $create_date=$request->create_date_downloadExcel;
             $registrant_state=$request->registrant_state_downloadExcel;
          $tdl_com=$request->tdl_com_downloadExcel;
          $tdl_net=$request->tdl_net_downloadExcel;
          $tdl_org=$request->tdl_org_downloadExcel;
          $tdl_io=$request->tdl_io_downloadExcel;

          $cell_number=$request->cell_number_downloadExcel;
          $landline=$request->landline_downloadExcel;

          $phone_number=array();
          if($cell_number=='1'){
            $phone_number[]='Cell Number';
          }
          if($landline=='1'){
            $phone_number[]='Landline';
          }
          $tdl=array();
          if($tdl_com==1){
           $tdl[]='com'; 
          }
          if($tdl_net==1){
           $tdl[]='net'; 
          }
          if($tdl_org==1){
           $tdl[]='org'; 
          }
          if($tdl_io==1){
           $tdl[]='io'; 
          }
          
          $registrant_country=$request->registrant_country_downloadExcel;
       
          $domain_name=$request->domain_name_downloadExcel;
          
          $requiredData=array();
          $leadusersData=array();
          $user_id=Auth::user()->id;


                 
            $exel_data = DB::table('leads')
                    ->join('domains', 'leads.id', '=', 'domains.user_id')
                    ->join('validatephone', 'validatephone.user_id', '=', 'leads.id')
                    ->select('leads.registrant_name as name','domains.domain_name as website','leads.registrant_address as address','leads.registrant_phone as phone','leads.registrant_email as email_id')
                    
                    ->where(function($query) use ($create_date,$domain_name,$registrant_country,$phone_number,$tdl,$registrant_state)
                      {
                          if (!empty($registrant_country)) {
                              $query->where('leads.registrant_country', $registrant_country);
                          } 
                          if (!empty($create_date)) {
                              $query->where('domains.create_date', $create_date);
                          } 
                          if (!empty($domain_name)) {
                             $query->where('domains.domain_name','like', '%'.$domain_name.'%');
                             
                          }
                          if(!empty($registrant_state))
                          {
                              $query->where('leads.registrant_state', $registrant_state);
                          }
                          if (!empty($phone_number)) {
                              $query->whereIn('validatephone.number_type', $phone_number);
                             
                          }
                           if (!empty($tdl)) {
                              $query->whereIn('domains.domain_ext', $tdl);
                             
                          }
                        
                      })
                 //->skip(0)
                 //->take(50)
                 ->groupBy('leads.registrant_email')
                 ->orderBy('domains.create_date', 'desc')
                 
                 ->get();

          } 
        }else{
          
            $domains_for_export=$request->domains_for_export;
            $domainsforexport=explode(",",$domains_for_export);
            $req_domainsforexport=array();
              foreach($domainsforexport as $val){
              $req_domainsforexport[]=$val; 
              }
              $exel_data=DB::table('domains')                         
                          ->join('leads', 'leads.id', '=', 'domains.user_id')
                          ->join('validatephone', 'validatephone.user_id', '=', 'leads.id')
                          ->select('leads.registrant_name as name','domains.domain_name as website','leads.registrant_address as address','leads.registrant_phone as phone','leads.registrant_email as email_id')
                          ->whereIn('domains.id',$req_domainsforexport)->get();   


        }




       $data = json_decode(json_encode($exel_data), true);
      // print_r($data);dd();

    return Excel::create('domainleads', function($excel) use ($data) {

      $excel->sheet('mySheet', function($sheet) use ($data)

          {

        $sheet->fromArray($data);

          });

    })->download($type);

  }
   public function getDomainData($id){
    

    $requiredData=array();
    $email=base64_decode($id);
     $requiredData = DB::table('leads')
              ->join('domains', 'leads.id', '=', 'domains.user_id')
              ->select('leads.*', 'domains.*')
               ->where('leads.registrant_email', $email)
               ->orderBy('domains.create_date', 'desc')
              ->get();
    
    return view('listDomain')->with('requiredData',$requiredData);
   }
  
   public function insertUserLeads(Request $request){

   // dd($request->all());
   // print_r($request->all()); dd();
      $total_leads=$request->total_leads;
      $user_id=$request->user_id;
      $leads_id=$request->leads_id;
      $domain_id=$request->domain_id;
       $used_leads=count(DB::table('leadusers')->select('id')->where('user_id',$user_id)->get());
        if($total_leads>$used_leads)
        {
            $date=date('Y-m-d H:i:s');
              $data=array(

              "user_id"=>$user_id,
              "leads_id"=>$leads_id,
              "domain_id"=>$domain_id,

              "created_at"=>$date,
              "updated_at"=>$date
               
               );
       
          DB::table('leadusers')->insert($data);
          echo $used_leads+1;

          $l = Lead::where('id',$leads_id)->first();
          $l->unlocked_num = $l->unlocked_num == null ? 1 :  $l->unlocked_num+1;
          $l->save();
        }else {
          echo "false";
        }
      
  }
  public function filteremailID(Request $request){

    ini_set("memory_limit","7G");
    ini_set('max_execution_time', '0');
    ini_set('max_input_time', '0');
    set_time_limit(0);
    ignore_user_abort(true); 
    
    //print_r($request->all()); dd();
    $domain_name=$request->domain_name;
    $registrant_country=$request->registrant_country;
    $create_date=$request->datepicker;
    $filteredemail=$request->filteredemail;
    $explodeemail=explode(",",$filteredemail);
    $notrequiredemail=array();
    foreach($explodeemail as $val){
      $notrequiredemail[]=$val; 
    }
    //print_r($notrequiredemail);dd();
      $requiredData = DB::table('leads')
              ->join('domains', 'leads.id', '=', 'domains.user_id')
              ->select('leads.*', 'domains.*')
              
              ->where(function($query) use ($create_date,$domain_name,$registrant_country)
                {
                    
                    if (!empty($domain_name)) {
                        $query->where('domains.domain_name', $domain_name);
                    }
                    if (!empty($registrant_country)) {
                        $query->where('leads.registrant_country', $registrant_country);
                    } 
                    if (!empty($create_date)) {
                        $query->where('domains.create_date', $create_date);
                    }
                })
               ->whereNotIn('leads.registrant_email', $notrequiredemail)
               ->orderBy('domains.create_date', 'desc')
              ->get();
   
     
          echo "<table class='table'>";
          echo  "<thead>";
          echo "<tr>";
          echo  "<th>Domain Name</th>";
          echo  "<th>Registrant Name</th>";
          echo  "<th>Registrant Email</th>";
          echo  "<th>Registrant Phone</th>";
          echo  "<th>Registered Date</th>";
          echo  "<th>Registrant Company</th>";
          echo  "<th>Registrant Address</th>";
          echo  "<th>Registrant City</th>";
          echo  "<th>Registrant State</th>";
          echo  "<th>Registrant Zip</th>";
          echo  "<th>Registrant Country</th>";
            
          echo  "<th>Expiry Date</th>";
          echo  "<th>Domain Registrar ID</th>";
          echo  "<th>Domain Registrar Name</th>";
          echo  "<th>Domain Registrar Whois</th>";
          echo  "<th>Domain Registrar Url</th>";
          echo  "</tr>";
          echo  "</thead>";
          echo "<tbody>";
          if(count($requiredData)){
           foreach($requiredData as $key=>$value) {
                
                echo "<tr>";
                echo "<td><a href='http://".$value->domain_name."' target='_blank'>".$value->domain_name."</a></td>";
                echo "<td>".$value->registrant_name."</td>";
                //echo "<td>".$value->email."<button class='btn btn-success' onclick='filterFunction(".$value->email.")'>Filter</button></td>";
                echo "<td>".$value->registrant_email;?><button class="btn btn-success" onclick="filterFunction('<?php echo $value->registrant_email; ?>')">Filter</button>&nbsp;<a href="getDomainData/<?php echo base64_encode($value->registrant_email); ?>" target="_blank"><button class="btn btn-success">View</button></a></td>
                 <?php
                echo "<td>".$value->registrant_phone."</td>";
                echo "<td>".$value->create_date."</td>";
                echo "<td>".$value->registrant_company."</td>";
                echo "<td>".$value->registrant_address."</td>";
                echo "<td>".$value->registrant_city."</td>";
                echo "<td>".$value->registrant_state."</td>";
                echo "<td>".$value->registrant_zip."</td>";
                echo "<td>".$value->registrant_country."</td>";
                echo "<td>".$value->expiry_date."</td>";
                echo "<td>".$value->domain_registrar_id."</td>";
                echo "<td>".$value->domain_registrar_name."</td>";
                echo "<td>".$value->domain_registrar_whois."</td>";
                echo "<td>".$value->domain_registrar_url."</td>";
                echo "</tr>";
              }
            }else {
              echo "<tr><td colspan='13'>No Result Found!!!</td></tr>";
            } 
         echo "</tbody>";
         echo  "</table>";
      
    //print_r($requiredData);
  } 

  
  public function importExport()

  {

    return view('importExport');

  }

  public function searchDomain()
  {


     $user_type=Auth::user()->user_type;
     $user_id=Auth::user()->id;
     $used_leads=count(DB::table('leadusers')->select('id')->where('user_id',$user_id)->get());
     //return 1;
   
      $leadusersData=array();
      $requiredData=array();    
      $total_leads=10;
       if($user_type=='1'){
        return view('searchDomain')->with('requiredData',$requiredData)->with('leadusersData',$leadusersData)->with('total_leads',$total_leads)->with('used_leads',$used_leads);
      }else{
        return view('searchDomainAdmin')->with('requiredData',$requiredData)->with('leadusersData',$leadusersData);
      }
  }

  
  public function importExcel()

  {
   
    ini_set("memory_limit","7G");
    ini_set('max_execution_time', '0');
    ini_set('max_input_time', '0');
    set_time_limit(0);
    ignore_user_abort(true);  
    if(Input::hasFile('import_file')){

      $path = Input::file('import_file')->getRealPath();

      $data = Excel::load($path, function($reader) {

      })->get();
      
      if(!empty($data) && $data->count()){
       
        foreach ($data as $key => $value) {

          if($value->registrant_email){
             unset($insert_domain);
             $date=date('Y-m-d H:i:s');
                $id_email=DB::table('leads')->select('id')->where('registrant_email',$value->registrant_email)->get();

                  if(count($id_email) ==0 ){
                  unset($insert);
                  $insert=array();
                  $insert =    ['registrant_name' => $value->registrant_name?$value->registrant_name:'', 
                               'registrant_company' => $value->registrant_company?$value->registrant_company:'',
                               'registrant_address' => $value->registrant_address?$value->registrant_address:'',
                               'registrant_city' => $value->registrant_city?$value->registrant_city:'',
                               'registrant_state' => $value->registrant_state?$value->registrant_state:'',
                               'registrant_zip' => $value->registrant_zip?$value->registrant_zip:'',
                               'registrant_country' => $value->registrant_country?$value->registrant_country:'',
                               'registrant_email' => $value->registrant_email?$value->registrant_email:'',
                               'registrant_phone' => $value->registrant_phone?$value->registrant_phone:'',
                               'registrant_fax' => $value->registrant_fax?$value->registrant_fax:'',
                               
                               "created_at"=>$date,
                               "updated_at"=>$date,
                              
                               ];
                    
          // print_r($insert);dd();
               
                     $user_id = DB::table('leads')->insertGetId($insert);
                       $job = (new validatephone($user_id,$value->registrant_phone));
                       $this->dispatch($job);
                     
                   
                  } else {
                      $user_id=$id_email[0]->id;

                  }
                  $domain_name=DB::table('domains')->select('id')->where('domain_name',$value->domain_name)->get();

                  if(count($domain_name) ==0 ){
                    $domain_ext=substr(strrchr($value->domain_name, "."), 1);
                    $insert_domain=array();
                    $insert_domain = ['domain_name' => $value->domain_name, 
                                 'query_time' => $value->query_time?$value->query_time:'',
                                 'create_date' => $value->create_date?$value->create_date:'',
                                 'update_date' => $value->update_date?$value->update_date:'',
                                 'expiry_date' => $value->expiry_date?$value->expiry_date:'',
                                 'domain_registrar_id' => $value->domain_registrar_id?$value->domain_registrar_id:'',
                                 'domain_registrar_name' => $value->domain_registrar_name?$value->domain_registrar_name:'',
                                 'domain_registrar_whois' => $value->domain_registrar_whois?$value->domain_registrar_whois:'',
                                 'domain_registrar_url' => $value->domain_registrar_url?$value->domain_registrar_url:'',
                                 'user_id' => $user_id,
                                 'administrative_name' => $value->administrative_name?$value->administrative_name:'',
                                 'administrative_company' => $value->administrative_company?$value->administrative_company:'',
                                 'administrative_address' => $value->administrative_address?$value->administrative_address:'',
                                 'administrative_city' => $value->administrative_city?$value->administrative_city:'',
                                 'administrative_state' => $value->administrative_state?$value->administrative_state:'',
                                 'administrative_zip' => $value->administrative_zip?$value->administrative_zip:'',
                                 'administrative_country' => $value->administrative_country?$value->administrative_country:'',
                                 'administrative_email' => $value->administrative_email?$value->administrative_email:'',
                                 'administrative_phone' => $value->administrative_phone?$value->administrative_phone:'',
                                 'administrative_fax' => $value->administrative_fax?$value->administrative_fax:'',
                                 'technical_name' => $value->technical_name?$value->technical_name:'',
                                 'technical_company' => $value->technical_company?$value->technical_company:'',
                                 'technical_city' => $value->technical_city?$value->technical_city:'',
                                 'technical_state' => $value->technical_state?$value->technical_state:'',
                                 'technical_zip' => $value->technical_zip?$value->technical_zip:'',
                                 'technical_country' => $value->technical_country?$value->technical_country:'',
                                 'technical_email' => $value->technical_email?$value->technical_email:'',
                                 'technical_phone' => $value->technical_phone?$value->technical_phone:'',
                                 'technical_fax' => $value->technical_fax?$value->technical_fax:'',
                                 'billing_name' => $value->billing_name?$value->billing_name:'',
                                 'billing_company' => $value->billing_company?$value->billing_company:'',
                                 'billing_address' => $value->billing_address?$value->billing_address:'',
                                 'billing_city' => $value->billing_city?$value->billing_city:'',
                                 'billing_state' => $value->billing_state?$value->billing_state:'',
                                 'billing_zip' => $value->billing_zip?$value->billing_zip:'',
                                 'billing_country' => $value->billing_country?$value->billing_country:'',
                                 'billing_email' => $value->billing_email?$value->billing_email:'',
                                 'billing_phone' => $value->billing_phone?$value->billing_phone:'',
                                 'billing_fax' => $value->billing_fax?$value->billing_fax:'',
                                 'name_server_1' => $value->name_server_1?$value->name_server_1:'',
                                 'name_server_2' => $value->name_server_2?$value->name_server_2:'',
                                 'name_server_3' => $value->name_server_3?$value->name_server_3:'',
                                 'name_server_4' => $value->name_server_4?$value->name_server_4:'',
                                 'domain_status_1' => $value->domain_status_1?$value->domain_status_1:'',
                                 'domain_status_2' => $value->domain_status_2?$value->domain_status_2:'',
                                 'domain_status_3' => $value->domain_status_3?$value->domain_status_3:'',
                                 'domain_status_4' => $value->domain_status_4?$value->domain_status_4:'',
                                 'domain_ext' => $domain_ext?$domain_ext:'',
                                 "created_at"=>$date,
                                 "updated_at"=>$date,
                                 ]; 
                   DB::table('domains')->insert($insert_domain);
          }     } 
        }
         
      //  if(!empty($insert)){//    DB::table('users')->insert($insert);//   }
        return redirect('importExport')->with("msg","Inserted Record successfully.");
   
        
      }

    }

    //return back();
     return view('importExport');
          
  }


  public function all_domain($email = null)
  {
    
    $email = base64_decode($email);
    $leads = Lead::where('registrant_email',$email);
    $leads_id = $leads->select('id')->get()->toArray();
    $domain = Domain::whereIn('user_id' , $leads_id);

    $alldomain = $domain->join('leads', 'domains.user_id', '=', 'leads.id')->get();
    //dd($alldomain);
    return view('all_domain',['alldomain'=>$alldomain,'email'=>$email ]);
  }


   public function postSearchData(Request $request)
  {   
    ini_set("memory_limit","7G");
    ini_set('max_execution_time', '0');
    ini_set('max_input_time', '0');
    set_time_limit(0);
    ignore_user_abort(true); 

      $create_date=$request->create_date;
      $tdl_com=$request->tdl_com;
      $tdl_net=$request->tdl_net;
      $tdl_org=$request->tdl_org;
      $tdl_io=$request->tdl_io;

      $cell_number=$request->cell_number;
      $landline=$request->landline;

      $phone_number=array();
      if($cell_number=='1'){
        $phone_number[]='Cell Number';
      }
      if($landline=='1'){
        $phone_number[]='Landline';
      }
      $tdl=array();
      if($tdl_com==1){
       $tdl[]='com'; 
      }
      if($tdl_net==1){
       $tdl[]='net'; 
      }
      if($tdl_org==1){
       $tdl[]='org'; 
      }
      if($tdl_io==1){
       $tdl[]='io'; 
      }
      
      $registrant_country=$request->registrant_country;

      $registrant_state = $request->registrant_state;
   
      $domain_name=$request->domain_name;
      
      $requiredData=array();
      $leadusersData=array();
      $user_id=Auth::user()->id;
      $user_type=Auth::user()->user_type;
      $leadusersData = DB::table('leadusers')
             ->select('*')
             ->where('user_id', $user_id)
             ->get();
      //dd($leadusersData);
      //print_r($leadusersData);dd();

           
      $requiredData = DB::table('leads')
              ->join('domains', 'leads.id', '=', 'domains.user_id')
              ->join('validatephone', 'validatephone.user_id', '=', 'leads.id')
              ->select('leads.*','leads.id as leads_id','domains.id as domain_id','validatephone.*',
                      'domains.domain_name','domains.create_date','domains.expiry_date','domains.domain_registrar_id','domains.domain_registrar_name','domains.domain_registrar_whois','domains.domain_registrar_url')
              
              ->where(function($query) use ($create_date,$domain_name,$registrant_country,$phone_number,$tdl,$registrant_state)
                {
                    if (!empty($registrant_country)) {
                        $query->where('leads.registrant_country', $registrant_country);
                    } 
                    if(!empty($registrant_state))
                    {
                        $query->where('leads.registrant_state', $registrant_state);
                    }
                    if (!empty($create_date)) {
                        $query->where('domains.create_date', $create_date);
                    } 
                    if (!empty($domain_name)) {
                       $query->where('domains.domain_name','like', '%'.$domain_name.'%');
                       
                    }
                    if (!empty($phone_number)) {
                        $query->whereIn('validatephone.number_type', $phone_number);
                       
                    }
                     if (!empty($tdl)) {
                        $query->whereIn('domains.domain_ext', $tdl);
                       
                    }
                  
                })
             //->skip(0)
             //->take(50)
             ->groupBy('leads.registrant_email')
             ->orderBy('domains.create_date', 'desc')
             ->paginate(100);
             //->get();
            
       $lead_id=array();
       foreach($leadusersData as $val){
          $lead_id[]=$val->leads_id;
        } 
       $total_leads='50';
       $used_leads=count(DB::table('leadusers')->select('id')->where('user_id',$user_id)->get());


       // to implement the domain count via email

       $count_domain = array();
       foreach($requiredData as $data)
       {
          if(!isset($count_domain[$data->registrant_email]))
          {
              $leads            = Lead::where('registrant_email' , $data->registrant_email);
              $leads_id         = $leads->select('id')->get()->toArray();
              $count_domain[$data->registrant_email] =  count(Domain::whereIn('user_id' , $leads_id)->get());
          }
       }
       // domain count ends

        if($user_type=='1')
        {
            return view('searchDomain')->with('requiredData', $requiredData)->with('leadusersData', $lead_id)->with('total_leads', $total_leads)->with('used_leads', $used_leads)->with('count_domain' , $count_domain); 
        }
        else 
        {
             return view('searchDomainAdmin')->with('requiredData', $requiredData)->with('leadusersData', $lead_id)->with('count_domain' , $count_domain);
        }
                             
    
         
         //return view::make('searchDomain',compact([
          //'requiredData' => $requiredData,
         //  'leadusersData' => $leadusersData, 
          
        //  ]));
          //return redirect('searchDomain')->with("requiredData",$requiredData); 
  }

  public function ajax(Request $request)

  {   
   
  
      $domains_for_export_id=$request->domains_for_export_id;
       $domains_for_export_id_allChecked=$request->domains_for_export_id_allChecked;
      $create_date=$request->create_date;
      $registrant_state=$request->registrant_state;
      
      $tdl_com=$request->tdl_com;
      
      $tdl_net=$request->tdl_net;
      
     $tdl_org=$request->tdl_org;
      
      $tdl_io=$request->tdl_io;


      $cell_number=$request->cell_number;
       $landline=$request->landline;

      $phone_number=array();
      if($cell_number=='1'){
        $phone_number[]='Cell Number';
      }
      if($landline=='1'){
        $phone_number[]='Landline';
      }
      $tdl=array();
      if($tdl_com==1){
       $tdl[]='com'; 
      }
      if($tdl_net==1){
       $tdl[]='net'; 
      }
      if($tdl_org==1){
       $tdl[]='org'; 
      }
      if($tdl_io==1){
       $tdl[]='io'; 
      }
      
      $registrant_country=$request->registrant_country;
   
      $domain_name=$request->domain_name;
      
      $requiredData=array();
      $leadusersData=array();
      $user_id=Auth::user()->id;
      $user_type=Auth::user()->user_type;
      $leadusersData = DB::table('leadusers')
             ->select('*')
             ->where('user_id', $user_id)
             ->get();
      //print_r($leadusersData);dd();

           
      $requiredData = DB::table('leads')
              ->join('domains', 'leads.id', '=', 'domains.user_id')
              ->join('validatephone', 'validatephone.user_id', '=', 'leads.id')
              ->select('leads.*','leads.id as leads_id','domains.id as domain_id','validatephone.*',
                      'domains.domain_name','domains.create_date','domains.expiry_date','domains.domain_registrar_id','domains.domain_registrar_name','domains.domain_registrar_whois','domains.domain_registrar_url')
              
              ->where(function($query) use ($create_date,$domain_name,$registrant_country,$phone_number,$tdl,$registrant_state)
                {
                    if (!empty($registrant_country)) {
                        $query->where('leads.registrant_country', $registrant_country);
                    } 
                    if (!empty($create_date)) {
                        $query->where('domains.create_date', $create_date);
                    } 
                    if (!empty($domain_name)) {
                       $query->where('domains.domain_name','like', '%'.$domain_name.'%');
                       
                    }
                    if(!empty($registrant_state))
                    {
                        $query->where('leads.registrant_state', $registrant_state);
                    }
                    if (!empty($phone_number)) {
                        $query->whereIn('validatephone.number_type', $phone_number);
                       
                    }
                     if (!empty($tdl)) {
                        $query->whereIn('domains.domain_ext', $tdl);
                       
                    }
                    
                  
                })
             //->skip(0)
             //->take(50)
             ->groupBy('leads.registrant_email')
             ->orderBy('domains.create_date', 'desc')
             ->paginate(100);
             //->get();
     
    $lead_id=array();
       foreach($leadusersData as $val){
       $lead_id[]=$val->leads_id;

       } 
    $domainsforexport=explode(",",$domains_for_export_id);
            $req_domainsforexport=array();
              foreach($domainsforexport as $val){
              $req_domainsforexport[]=$val; 
              }    
    if($user_type==1){
     return view('searchDomain_ajax')->with('requiredData',$requiredData)->with('leadusersData', $lead_id)->with('req_domainsforexport', $req_domainsforexport)->with('domains_for_export_id_allChecked', $domains_for_export_id_allChecked)->render(); 
    }else {
     return view('searchDomainAdmin_ajax')->with('requiredData',$requiredData)->with('leadusersData', $lead_id)->with('req_domainsforexport', $req_domainsforexport)->with('domains_for_export_id_allChecked', $domains_for_export_id_allChecked)->render();        
    }
    
  }

}