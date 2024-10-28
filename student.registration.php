<head>
  <link rel="stylesheet" href="../../inc/ea-style.css">
  <title>Registration server</title>

    <script src="https://www.google.com/recaptcha/api.js"></script>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PCM3G5D6');</script>
    <!-- End Google Tag Manager -->


</head>

<style>
td {width: 320px; padding:1px; vertical-align: top;}
body {color:black;}
input, datalist, textarea {color:black;font-weight: bold;}
input, textarea {background-color:white;}
</style>

  <script>
    function disableButton() {
      // Získá tlačítko
      var button = document.querySelector('.button');
      
      // Deaktivuje tlačítko
      button.disabled = true;
      
      // Nastaví časovač na 5 sekund (5000 ms) a po uplynutí tohoto času znovu aktivuje tlačítko
      setTimeout(function() {
        button.disabled = false;
      }, 7000);
    }
    

  </script>
<body>
    <div>    
      <table width=100% style="border:0;"><tr align=center><td>
        <img width=550 src=./logo-<?php print $_REQUEST['lang'];?>.png>
      </table>
    </div>
<?php
error_reporting(E_ERROR | E_STRICT);
ini_set("display_errors", 1);

session_start();

function getCountryCodeByIP2($ip) {
    $url = "http://ip-api.com/json/{$ip}?fields=countryCode";
    $url = "http://ip-api.com/php/$ip?fields=countryCode";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return isset($data['countryCode']) ? $data['countryCode'] : false;
}

function getCountryCodeByIP($ip) {
    // Příklad URL pro jinou službu (např. IPInfo)
    $url = "https://ipinfo.io/{$ip}/json";

    // Odeslání požadavku na API
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Vrácení kódu země, pokud je dostupný
    return isset($data['country']) ? $data['country'] : false;
}

$ip = $_SERVER['REMOTE_ADDR'];
$countryCode = getCountryCodeByIP($ip);
//echo "Country code: " . $countryCode;


//ini_set('session.gc_maxlifetime', 604800);
$elang=$_SERVER['HTTP_ACCEPT_LANGUAGE'];$elang=strtolower(trim(substr($elang,0,2) ) );

$sid=date("Ym").rand(999,9999);
//require_once('../../lib/mpdf60/mpdf.php');
require_once('./function.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


require_once('../../lib/vendor/autoload.php');

$mode=$_REQUEST['mode'];
$id=$_REQUEST['id'];
$ext=$_REQUEST['ext'];
$lang=$_REQUEST['lang'];

//print bin2hex($currentDate);

if($_REQUEST)
{
    $ref=$_SERVER['HTTP_REFERER'];
    $link="";
    foreach($_REQUEST AS $nf=>$vf){$link.="$nf=$vf; ";}
    $f=fopen("reg.log","a");fwrite($f,$currentDate." | $ip | $ref | ".$link."\n");fclose($f); 

}

//$db = new PDO('sqlite:../../inc/study_plan.db');

//load youtube data to array from youtube.txt
$lines = file('youtube.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$youtubedata = [];

foreach ($lines as $line) {
    // Rozdělení každého řádku podle středníku (;)
    $parts = explode(';', $line);

    // Přidání rozdělených dat do výsledného pole
/*
    $youtubedata[] = [
        'id' => $parts[0],
        'language' => $parts[1],
        'code' => $parts[2]
    ];
*/
    $youtubedata[$parts[0]][$parts[1]] = $parts[2];

}

//end

//print $youtubedata[115][cz];
//print_r($youtubedata);


if($mode=="activate"){

    $sql="UPDATE students SET active=1 WHERE id=$id;";
    $data=$db->query($sql);

    print "<h2>$txtractivation</h2>";

}

if($mode=="delstudy"){

    $idpgm=$_REQUEST['idpgm'];
    $sql="DELETE FROM students_programs WHERE id=$idpgm;";
    $data=$db->query($sql);

    print "<meta http-equiv='refresh' content='0; url=./student.registration.php?mode=study&id=$id' >";


}

if($mode=="startstudy"){

    $study_pgm=$_REQUEST['study_pgm'];
    $idpgm=$_REQUEST['idpgm'];

    $sql="UPDATE students_programs SET active=1,start=CURRENT_DATE WHERE id='$idpgm';";
    $data=$db->query($sql);


    $sql="SELECT * FROM $db_study_plans WHERE zamereni='$study_pgm';";
    $data=$db->query($sql);
    $data -> execute();

    while ($result = $data -> fetch() )
    {

        print $sql2="INSERT INTO students_subjects (sid,study_pgm,subject) VALUES ('$id','$study_pgm','".$result[sub]."');\n";
        $data2=$db->query($sql2);

        print "<br>";

        //zadání seminárky 
        $sql3="SELECT id,count(*) AS count FROM exam WHERE subject='".$result[sub]."' AND typ='zs';\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $count=$result3[count];
        $ide=$result3[id];
        if($count>0){
        
        $sql3="SELECT * FROM exam_data WHERE id_exam='$ide' ORDER BY RANDOM() LIMIT 1;\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $ideq=$result3[id];

        $sql3="INSERT INTO students_exams (sid,subject,exam_id,exam_quest,exam_type) VALUES('$id','".$result[sub]."','$ide','$ideq','zs');";
        $data3=$db->query($sql3);
        print "&nbsp;----> $sql3<br>";
        
        }

        //zadání testu 
        $sql3="SELECT id,count(*) AS count FROM exam WHERE subject='".$result[sub]."' AND typ='zt';\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $count=$result3[count];
        $ide=$result3[id];
        if($count>0){
        
        $sql3="INSERT INTO students_exams (sid,subject,exam_id,exam_quest,exam_type) VALUES('$id','".$result[sub]."','$ide','','zt');";
        $data3=$db->query($sql3);
        print "&nbsp;----> $sql3<br>";
        
        
        }

        //zadání prezentace 
        $sql3="SELECT id,count(*) AS count FROM exam WHERE subject='".$result[sub]."' AND typ='zp';\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $count=$result3[count];
        $ide=$result3[id];
        if($count>0){
        
        $sql3="SELECT * FROM exam_data WHERE id_exam='$ide' ORDER BY RANDOM() LIMIT 1;\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $ideq=$result3[id];
        
        $sql3="INSERT INTO students_exams (sid,subject,exam_id,exam_quest,exam_type) VALUES('$id','".$result[sub]."','$ide','$ideq','zp');";
        $data3=$db->query($sql3);
        print "&nbsp;----> $sql3<br>";
        
        
        }

        //zadání projektu/výzkumu 
        $sql3="SELECT id,count(*) AS count FROM exam WHERE subject='".$result[sub]."' AND typ='pro';\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $count=$result3[count];
        $ide=$result3[id];
        if($count>0){
        
        $sql3="SELECT * FROM exam_data WHERE id_exam='$ide' ORDER BY RANDOM() LIMIT 1;\n";
        $data3=$db->query($sql3);
        $result3 = $data3 -> fetch();
        $ideq=$result3[id];
        
        $sql3="INSERT INTO students_exams (sid,subject,exam_id,exam_quest,exam_type) VALUES('$id','".$result[sub]."','$ide','$ideq','pro');";
        $data3=$db->query($sql3);
        print "&nbsp;----> $sql3<br>"; 
        
        
        }


    }

    print "<meta http-equiv='refresh' content='3; url=./student.registration.php?mode=study&id=$id' >";


}

if($mode=="program"){

        print "<hr>";$n=0;
        $study_pgm=$_REQUEST[study_pgm];
        
        $sql="SELECT * FROM $db_students_subjects WHERE sid='$id' AND study_pgm='$study_pgm';";
        $data=$db->query($sql);
        $data -> execute();
        
        print "<table border=1>";
        print "<tr align=center><th>no.<th>fce.<th>Subject<th>Test/Credit<th>Success<th>Date<th>Files";
        
        while ($result = $data -> fetch() )
        {
            $n++;
            
            $subject=$result[subject];
            
            $sql2="SELECT * FROM $db_subjects WHERE id='$subject';";
            $data2=$db->query($sql2);
            $result2 = $data2 -> fetch();
            
            $subject.=" ".$result2[subject]." ".$result2[ukonceni]."/".$result2[ECTS]."/".$result2[typ];

            if($result[success]>50){$color="#CCFFCC";}
            else{$color="#FFE0E0";}
            print "<tr style='background-color:$color'><td align=right>$n.<td>-<td>$subject<td>".$result[credit]."<td>".$result[success]."<td><td>-";

            $sql3="SELECT * FROM $db_students_exams WHERE sid='$id' AND subject='".$result[subject]."';";
            $data3=$db->query($sql3);

            while($result3 = $data3 -> fetch())
            {
                $type=$test=$result3[exam_type];
                if($result3[success]>50){$color="#CCFFCC";}
                else{$color="#FFE0E0";}

                $sql4="SELECT * FROM exam_data WHERE id='".$result3[exam_quest]."';";
                $data4=$db->query($sql4);
                $result4 = $data4 -> fetch();
                if($type<>"zt"){$test.=" ".$result4[desc];}

                print "<tr style='background-color:$color' ><td><td>accept<td><td>$test<td>".$result3[success]."<td>".$result3[date]."<td>".$result3[doc_id]."";
            
            }



        }

        print "</table><a href=?mode=study&id=$id class=button>Back to study programs</a>";

}

if($mode=="study"){

        print "<hr>";$n=0;

        //print $sql="SELECT * FROM students_programs WHERE sid='$id' ORDER BY created DESC;";
        $sql="SELECT *,students_programs.id AS idpgm FROM students,students_programs,zamereni WHERE students_programs.sid='$id' AND students_programs.study_pgm=zamereni.id AND students_programs.sid=students.id ORDER BY created DESC;";
        $data=$db->query($sql);
        $data -> execute();
        
        print "<table border=1>";
        print "<tr align=center><th>no.<th>fce.<th>Study program<th>Lang<th>Start<th>End<th>Credit<th>Creaded";
        
        while ($result = $data -> fetch(PDO::FETCH_ASSOC) )
        {
            $n++;

            //$sql2="SELECT * FROM zamereni WHERE id='".$result[study_pgm]."';";
            //$data2=$db->query($sql2);
            //$result2 = $data2 -> fetch();
            
            //$study=$result[study_pgm]." ".$result2[name]." / ".$result2[focus];
            $study=$result[study_pgm]." ".$result[name]." / ".$result[focus];

            if($result[active]){$color="#CCFFCC";}
            else{$color="#FFE0E0";}

	    $resultX=$result;
	    foreach($resultX as $fieldx=>$datax)
	    {
	    //$body.="<div>$field: <b>$data</b></div>";
            $datax=urlencode($datax);
            if($_REQUEST['lang']=="cz" AND $fieldx=="dn"){$datax=date2cz($datax);}
            if($fieldx<>"id" AND $fieldx<>"mode" ){$req.="$fieldx=$datax&";}
	    }

            print "<tr style='font-weight:normal; font-size:14;' align=center bgcolor=$color><td>$n
	    <td>
		<a href=?mode=delstudy&id=$id&idpgm=".$result[idpgm]." onclick=\"return confirm('Are you sure?')\">Del</a>|
		<a href=?mode=startstudy&id=$id&study_pgm=".$result[study_pgm]."&idpgm=".$result[idpgm].">Start</a>|
		<a target=_blank href=./document.pdf.php?id=4001&lang=cz&$req>ConCZ</a>|
		<a target=_blank href=./document.pdf.php?id=4001&lang=en&$req>ConEN</a>|
	    <td><a href=?mode=program&id=$id&study_pgm=".$result[study_pgm].">$study<td>".$result[lang]."<td>".$result[start]."<td>".$result[end]."<td>".$result[credit]."<td>".$result[created];

        }

        print "</table>";
        print "<a href=?mode=list class=button>Back to Students</a>";

}

if($mode=="deldocument"){

    $idd=$_REQUEST['idd'];
    $file=$_REQUEST['file'];
    $sql="DELETE FROM files WHERE id=$idd;";
    $data=$db->query($sql);

    unlink($file);
    
    print "<meta http-equiv='refresh' content='0; url=./student.registration.php?mode=document&id=$id' >";


}

if($mode=="documentuploadsave"){

    mkdir("./students_docs/$id");
    //print_r($_FILES);
    $tmpname=$_FILES[file][tmp_name];
    $fname=$_FILES[file][name];
    $size=$_FILES[file][size];

    $filename=$_REQUEST['filename'];

    $pathinfo = pathinfo($fname);
    $type = $pathinfo['extension'];
    
    foreach (glob("./students_docs/$id/*") as $soubor) {
        $ns++;
    }
    
    $ns++;
    $name="$ns"."_"."$filename";
    move_uploaded_file($tmpname,"./students_docs/$id/$name.$type");
    
    $sql="INSERT INTO files (sid,filename,ext,type,size) VALUES ('$id','$name','$type','$filename','$size');";
    $data=$db->query($sql);
    
   
    print "<meta http-equiv='refresh' content='0; url=./student.registration.php?mode=document&id=$id' >";

}

if($mode=="document"){

        print "<hr>";$n=0;

        $sql="SELECT * FROM files WHERE sid='$id' ORDER BY created DESC;";
        $data=$db->query($sql);
        $data -> execute();
        
        print "<table border=1>";
        print "<tr align=center><th>no.<th>fce.<th>Name<th>Type<th>Ext.<th>Size<th>Creaded";
        
        while ($result = $data -> fetch() )
        {
            $n++;
            $size=ceil($result[size]/1024);
            $filename=str_replace(" ","%20",$result[filename]);
            $ext=$result[ext];
            $sid=$result[sid];
            print "<tr align=center><td>$n<td><a href=?mode=deldocument&id=$id&file=./students_docs/$sid/$filename.$ext&idd=".$result[id]." onclick=\"return confirm('Are you sure?')\">Del</a><td align=left><a target=_blank href=./students_docs/$sid/$filename.$ext>".$result[filename]."</a><td>".$result[type]."<td>".$result[ext]."<td>$size kB<td>".$result[created];

        }

        print "</table>";

        print "<a href=?mode=list class=button>Back to Students</a>";

        print "<form method=post enctype='multipart/form-data'>";

        print "<br><br><select name=filename>";
        print "<option>ID document</option>";
        print "<option>Education document</option>";
        print "<option>Contract</option>";
        print "<option>Other doc</option>";
        print "</select>";

        print "<input type=hidden name=mode value=documentuploadsave>";
        print "<input type=hidden name=id value=$id>";
        print "<input type=file name=file><br><br>";
        print "<input type=submit class=button onclick='disableButton(this)'>";
        
}


if($mode=="del"){

    $sql="DELETE FROM students WHERE id='$id';";
    $data=$db->query($sql);

    $sql="DELETE FROM students_programs WHERE sid='$id';";
    $data=$db->query($sql);

    $sql="DELETE FROM students_doc WHERE sid='$id';";
    $data=$db->query($sql);

    $sql="DELETE FROM students_subjects WHERE sid='$id';";
    $data=$db->query($sql);

    $sql="DELETE FROM students_exams WHERE sid='$id';";
    $data=$db->query($sql);

    tempdel("./students_docs/$id/*.*");
    rmdir("./students_docs/$id");

    print "<meta http-equiv='refresh' content='0; url=./student.registration.php?mode=list' >";

}


if(!$_REQUEST['study'] AND !$mode){

    if($lang=="cs" OR $lang=="cz")
    {
    
        $ini = parse_ini_file("cz.lang.ini");
        //print_r($ini);
        foreach($ini as $key=>$prom)
        {
        
            $eval='$'.$key.'="'.$prom.'";';
            eval($eval);
                
        }
    
    }
    
    else
    {
    
        $ini = parse_ini_file("en.lang.ini");
        //print_r($ini);
        foreach($ini as $key=>$prom)
        {
        
            $eval='$'.$key.'="'.$prom.'";';
            eval($eval);
        
        }

    }


    print "
      <script>
         function onSubmit(token) {
           document.getElementById(\"form\").submit();
         }
      </script>
    ";
 
    print "<style>
    table { font-family:\"Playfair Display\"; border: 0px;} 
    input {font-family:\"Playfair Display\";}
    select {font-family:\"Playfair Display\";}
    tr {background-color:transparent;}
    td {font-size: 15px;}
    legend {font-family:\"Playfair Display\";}
    </style>\n\n";
    print "<fieldset>\n <legend>$txtapp</legend>\n";
    print "<form id=form onsubmit='disableButton()'>\n";
    
    print "\n\n<div><table style=''>";
    
    print "<tr><td>$txtstudypgm<td><select name=study style='width:300px;' >\n\n";
    
    $sql="SELECT * FROM $db_study_programs WHERE visible=1 ORDER BY id ASC";
    $data=$db->query($sql);
    $data -> execute();
    while ($result = $data -> fetch() )
    {
        $idz=$result[id];
        $name=$result[name];
        $focus=$result[focus];
        
        $price[$idz]="'<i><b>".$result[czk]."CZK / €".$result[eur]."</b></i>'";
        $program[$idz]="$name";
        
        if($lang=="cz"){$conditions[$idz]="'".$result[podminky]."<hr>".$result[volitelne]."<hr>'";}
        else{$conditions[$idz]="'".$result[condition]."<hr>".$result[custom]."<hr>'";}

        if($id==$idz){$sel="selected";}
        else{$sel="";}
    
            if ( ($idz>=30 AND $idz<=39) AND ($type<>"SOU") ) {print "<optgroup label='$txtrsou'>";$type="SOU";}
            if ( ($idz>=40 AND $idz<=49) AND ($type<>"SS") ) {print "<optgroup label='$txtrss'>";$type="SS";}
            if ( ($idz>=60 AND $idz<=89) AND ($type<>"PS") ) {print "<optgroup label='$txtrps'>";$type="PS";}
            if ( ($idz>=100 AND $idz<=199) AND ($type<>"PK") ) {print "<optgroup label='$txtrpk'>";$type="PK";}
            if ( ($idz>=200 AND $idz<=299) AND ($type<>"C") ) {print "<optgroup label='$txtrcourse'>";$type="C";}
        
        print "<option value=$idz $sel>$name | $focus</option>\n";
    }
    
    print "</select>\n";
    
    print "<tr><td>$txttitul1<td><input name=titul1 type=text placeholder='Dr., Ing.'>\n";
    print "<tr title='$txtrreq'><td>$txtfirstname*<td><input type=text name=firstname placeholder='$txtfirstname' size=15 required pattern=\"^[^@]+$\"><input type=text name=surname placeholder='$txtlastname' size=15 required pattern=\"^[^@]+$\">\n";
    print "<tr><td>$txttitul2<td><input type=text name=titul2 placeholder='MBA, PhD.'>\n";
    print "<tr><td style='border-bottom: 0.5px dashed blue;'>$txtrgender *<td style='border-bottom: 0.5px dashed blue;'>$txtrmale<input type=radio name=gender value=m required> | $txtrfemale<input type=radio name=gender value=f> | $txtrother<input type=radio name=gender value=o> |\n";
    print "<tr title='$txtrreq'><td>$txtaddr1*<td><input type=text name=addr1 required>\n";
    print "<tr><td>$txtaddr2<td><input type=text name=addr2>\n";
    print "<tr title='$txtrreq'><td>$txtcity*<td><input type=text name=city size=12 required><input type=text name=zip placeholder='$txtzip' size=8 required> ";
    
        print "<select name=country>\n";
        
        print $sql="SELECT * FROM countries ORDER BY code";
        $data=$db->query($sql);
        while ($result = $data -> fetch() )
        {
            $code=$result[code];
            $country=$result[country];
            if($lang=="cz"){$country=$result[cz];}
    
            if($code==$countryCode){$sel="selected";}
            else{$sel="";}
        
            print "<option value=$code $sel >$country</option>\n";
        }
        
        print "</select>";
    print "\n";

    print "<tr title='$txtrreq'><td>$txttel*<td><input type=text name=tel required>\n";
    print "<tr title='$txtrreq'><td>$txtconemail*<td><input name=email type=email required>\n";
//    print "<tr><td>$txthighedu<td><input name=vzdelani required>*\n";
    print "<tr title='$txtrreq'><td style='border-bottom: 0.5px dashed blue;'>$txthighedu*<td style='border-bottom: 0.5px dashed blue;'><select name=eqf required style='font-size:12px; width:110px;'>\n";
    print "<option value=1>$txtrege1</option>\n";
    print "<option value=2>$txtrege2</option>\n";
    print "<option value=3>$txtrege3</option>\n";
    print "<option value=4>$txtrege4</option>\n";
    print "<option value=6>$txtrege6</option>\n";
    print "<option value=7>$txtrege7</option>\n";
    print "<option value=8>$txtrege8</option>\n";
    print "</select>\n";

    print "<tr title='$txtrreq'><td>$txtdn*<td><input name=dn type=date required><input name=bornplace placeholder='$txtbirdplace' size=12 required>\n";

        print "<select name=bornplacecountry>\n";
        
        print $sql="SELECT * FROM countries ORDER BY code";
        $data=$db->query($sql);
        while ($result = $data -> fetch() )
        {
            $code=$result[code];
            $country=$result[country];
            if($lang=="cz"){$country=$result[cz];}
    
            if($code==$countryCode){$sel="selected";}
            else{$sel="";}
        
            print "<option value=$code $sel>$country</option>\n";
        }
        
        print "</select>";
    print "\n";

    print "<tr><td style='border-bottom: 0.5px dashed blue;'>$txtrc<td style='border-bottom: 0.5px dashed blue;'><input name=rc type=number>\n";

    print "<tr title='$txtrreq'><td>$txtfirstname_p<td><input type=text name=firstname_p placeholder='$txtfirstname' size=15 pattern=\"^[^@]+$\"><input type=text name=surname_p placeholder='$txtlastname' size=15 pattern=\"^[^@]+$\">\n";
    print "<tr title='$txtrreq'><td>$txtdn_p<td><input name=dn_p type=date >\n";

    print "<tr><td style='border-bottom: 0.5px dashed blue;'><td style='border-bottom: 0.5px dashed blue;'>\n";
    
    //print "<tr><td>$txtpreflang<td><input name=lang type=number required>*\n";
    $txtlangst=explode(":",$txtlangst);
    $txtlangstcode=explode(":",$txtlangstcode);
    $txtcurrcode=explode(":",$txtcurrcode);
    $n=0;
    print "<tr title='$txtrreq'><td>$txtpreflang*<td><select name=lang>\n";
    
    foreach($txtlangst as $lng)
    {
        $id=$txtlangstcode[$n];$n++;

        if($id==$lang){$sel="selected";}
        else{$sel="";}
        
        print "<option value=$id $sel>$lng</option>";
    }
    
    print "</select>\n";

    print "<tr title='$txtrreq'><td>$txtcurr*<td><select name=currency>\n";
    
    foreach($txtcurrcode as $lng)
    {
        $id=$txtcurrcode[$n];$n++;
        if($lang=="en" and $lng=="€"){$sel="selected";}
        print "<option $sel>$lng</option>";
        $sel="";
    }
    
    print "</select>\n";
    
    print "<tr><td>$txtrbookform*<td>$txtrpaperbook <input type=radio name=book value=paper>| $txtrebook <input type=radio name=book type=radio value=ebook_pdf checked><br>";

    print "<tr><td>$txtrform*<td>$txtrpresention <input type=radio name=studyform value=p>| $txtrcombine <input type=radio name=studyform type=radio value=c checked>| $txtronline <input name=studyform type=radio value=d><br>
    <small><a href=https://prace.estudium.eu/app/study/document.pdf.php?lang=cz&id=5009 target=_blank>$txtrforminfo</a></small>";
    
    print "<tr><td style='border-bottom: 0.5px dashed blue;'><td style='border-bottom: 0.5px dashed blue;'>\n";
    
    print "<tr><td>$txtrcomp<td><input name=reqcomp type=checkbox id='reqcompCheckbox'>\n";
    
    print "<tr id=\"invoiceFields1\" style=\"display: none;\"><td>$txtrico<td><input name=ico placeholder='12345678'>";
    print "<tr id=\"invoiceFields2\" style=\"display: none;\"><td>$txtrfemail<td><input name=bck_email>";
    print "<tr id=\"invoiceFields3\" style=\"display: none;\"><td>$txtrcompdata<td><textarea cols=30 rows=3 name=company placeholder='Company name and address'></textarea>";
    
        print "<tr id=\"invoiceFields4\" style=\"display: none;\"><td>$txtinregc<td><select name=ccountry>\n";
        
        print $sql="SELECT * FROM countries ORDER BY code";
        $data=$db->query($sql);
        while ($result = $data -> fetch() )
        {
            $code=$result[code];
            $country=$result[country];
            if($lang=="cz"){$country=$result[cz];}
    
            if($code==$countryCode){$sel="selected";}
            else{$sel="";}
        
            print "<option value=$code $sel >$country</option>\n";
        }
        
        print "</select>";
    print "\n";



    print "<tr><td>$txtregnote<td><textarea cols=35 rows=3 name=Note placeholder='$txtregnoteph'></textarea>\n";

    print "<tr><td>$txtrsale<td><input name=sale type=text>\n";
    print "<tr><td>$txtspl<td><input name=spl type=checkbox id='splCheckbox'> <a href=https://www.europeanacademy.cz/installments target=_blank><span id='installmentText'></span></a>\n";
    print "<tr><td>$txtratttext*<td><input name=control_form type=checkbox required><span style='font-size: 11px;'><b>$txtrattdesc</b></span>\n";
    print "<tr title='$txtrreq'><td style='border-bottom: 2.5px dashed blue;'>$txtaccgdpr*<td style='border-bottom: 2.5px dashed blue;'><input name=accept type=checkbox id='acceptCheckbox' required><div><a href=https://www.europeanacademy.cz/documents target=_blank><span id='docText' style='font-size: 11px;'></span></a></div>\n";

    print "<tr><td><b>$txtrtotal<td><span style='font-weight:bold; font-size:20px;' id='priceDisplay'>-</span>\n";
    print "<tr><td><b><td><span style='font-weight:bold;' id=\"programDisplay\">-</span>\n";
    
    print "<div style='position: fixed;width:280px;right:20px;top:140px;opacity: 0.7;background-color:lightgray;border:1px outset black;border-radius:3px;'><div><b>$txtcondition:</b></div><div id=condition></div><div id='priceDisplay2'></div></div>";

    print "<input name=mode value=savereg type=hidden>\n\n";
    $keycurrentDate=bin2hex($currentDate);
    print "<input name=key value=$keycurrentDate type=hidden>\n\n";
    //print "<input name=#up type=hidden>";
    
    //print "<div class='g-recaptcha' data-sitekey='6LfuSNklAAAAAE8X40_s6MZk-3xIHT7P1U8vHLcv'></div>\n\n";

    //print "</table></div><div>\n\n<button class=\"g-recaptcha\" data-sitekey=\"6LfuSNklAAAAAE8X40_s6MZk-3xIHT7P1U8vHLcv\" data-callback='onSubmit' data-action='submit' >$txtsubapp</button>\n</form></div>\n</fieldset>";
    print "</table></div><div>\n\n<input type=submit value='$txtsubapp' >\n</form></div>\n</fieldset>";

    print "
            <script>


            // Define an object with prices for each study program
            const prices = {\n";

            foreach($price AS $pgm=>$value)
            {
            
                print "$pgm: $value,\n";
            
            }
              //81: 8000,
              // Add the rest of the programs with their respective prices

    print "
            };

            const programs = {\n";

            foreach($program AS $pgm=>$value)
            {
            
                if($youtubedata[$pgm][$lang]){$yb="<iframe width=500 height=300 src=https://www.youtube-nocookie.com/embed/".$youtubedata[$pgm][$lang]." frameborder=0 allow=encrypted-media allowfullscreen></iframe>";}
                print "$pgm: '$value<br>$yb',\n";
            
            }

    print "
            };

            const conditions = {\n";

            foreach($conditions AS $pgm=>$value)
            {
            
                print "$pgm: $value,\n";
            
            }
              //81: 8000,
              // Add the rest of the programs with their respective prices

    print "
            };

          
            const condition = document.getElementById('condition');
            const priceDisplay = document.getElementById('priceDisplay');
            const priceDisplay2 = document.getElementById('priceDisplay2');
            const programDisplay = document.getElementById('programDisplay');
            const studyProgramSelect = document.querySelector('select[name=\"study\"]');
          
            function updatePrice() {
              const selectedProgram = studyProgramSelect.value;
              const price = prices[selectedProgram] || 'Neznámá cena';
              const program = programs[selectedProgram] || '';
              const conditionv = conditions[selectedProgram] || '';
              priceDisplay.innerHTML = price;
              priceDisplay2.innerHTML = price;
              programDisplay.innerHTML = program;
              condition.innerHTML = conditionv;
            }
          
            // Update the price when the user selects a different study program
            studyProgramSelect.addEventListener('change', updatePrice);
          
            // Update the price when the user changes the currency
            document.querySelector('select[name=\"currency\"]').addEventListener('change', updatePrice);
          
            // Update the price on page load
            updatePrice();

            const splCheckbox = document.getElementById('splCheckbox');
            const installmentText = document.getElementById('installmentText');
          
            function updateInstallmentText() {
              if (splCheckbox.checked) {
                installmentText.textContent = '$txtrinst';
              } else {
                installmentText.textContent = '';
              }
            }
          
            // Update the text when the user checks/unchecks the checkbox
            splCheckbox.addEventListener('change', updateInstallmentText);
          
            // Update the text on page load
            updateInstallmentText();

            const acceptCheckbox = document.getElementById('acceptCheckbox');
            const docText = document.getElementById('docText');

            function updatedocText() {
              if (acceptCheckbox.checked) {
                docText.textContent = '$txtconacc ';
              } else {
                docText.textContent = '';
              }
            }
          
            // Update the text when the user checks/unchecks the checkbox
            acceptCheckbox.addEventListener('change', updatedocText);
          
            // Update the text on page load
            updatedocText();

            const reqcompCheckbox = document.getElementById('reqcompCheckbox');
            const invoiceFields1 = document.getElementById('invoiceFields1');
            const invoiceFields2 = document.getElementById('invoiceFields2');
            const invoiceFields3 = document.getElementById('invoiceFields3');
            const invoiceFields4 = document.getElementById('invoiceFields4');
          
            function updateInvoiceFieldsVisibility() {
              if (reqcompCheckbox.checked) {
                invoiceFields1.style.display = 'table-row';
                invoiceFields2.style.display = 'table-row';
                invoiceFields3.style.display = 'table-row';
                invoiceFields4.style.display = 'table-row';
              } else {
                invoiceFields1.style.display = 'none';
                invoiceFields2.style.display = 'none';
                invoiceFields3.style.display = 'none';
                invoiceFields4.style.display = 'none';
              }
            }
          
            // Update the visibility of the input and textarea when the user checks/unchecks the checkbox
            reqcompCheckbox.addEventListener('change', updateInvoiceFieldsVisibility);
          
            // Update the visibility on page load
            updateInvoiceFieldsVisibility();

            </script>
          
      ";

    //print_r($_SESSION);
    //phpinfo();

}

$firstname = $_REQUEST['firstname'];
$surname = $_REQUEST['surname'];

if($mode=="savereg_part" AND $_REQUEST['accept0']=="1" AND $_REQUEST['key']==bin2hex($currentDate) )
{

    foreach($_REQUEST as $field=>$data)
    {
        $body.=urlencode("<div>$field: <b>$data</b></div>");
        //$data=urlencode($data);
        if($_REQUEST['lang']=="cz" AND $field=="dn"){$data=date2cz($data);}
        if($field<>"id" AND $field<>"mode" ){$req.="$field=$data&";}
    }

    $email = $_REQUEST['email'];
    print $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=new&mode=info&email=$email&body=$body";
    $content = file_get_contents($url);


}

if($mode=="savereg" AND $_REQUEST['control_form']=="on" AND $_REQUEST['key']==bin2hex($currentDate) AND $_REQUEST['email'] AND $_REQUEST['firstname'] AND strpos($firstname, '@') == false AND strpos($surname, '@') == false)
{

    //check double registration by email
    if($_REQUEST['email'])
    {
    
        $sql="SELECT count(*) as sum,id,created FROM $db_students WHERE email='".$_REQUEST['email']."' LIMIT 1;";
        $data=$db->query($sql);//$data -> execute();
        $result = $data -> fetch();
        $sum=$result[sum];
        $stid=$result[id];
        $created=$result[created];
        if($sum>0){die("<style>
              body {
                margin: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
              }
            
              span.centered-text {
                font-family: \"Playfair Display\";
                font-size: 24px;
              }
        </style><span class='centered-text'>$txtregdouble $stid<br>|$created|</span>");}
    }
    

    //print "<table width=100% height=800><tr width=100%><td width=100% style='position: absolute; top: 50%;'><div style=\"font-family:'Lora'; font-size: 24px; width:100%;\" >$txtappformsend</div></table>";
/*
    print "<br>\n<style>
              body {
                margin: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
              }
            
              span.centered-text {
                font-family: \"Playfair Display\";
                font-size: 24px;
              }
        </style><span class='centered-text'>$txtappformsend</span>";
*/
    print "<table><tr><tr><td><h3>$txtappformsend</h3></table>";
    
    $studentname=$_REQUEST['titul1']." ".$_REQUEST['firstname']." ".$_REQUEST['surname']." ".$_REQUEST['titul2'];
    
    foreach($_REQUEST as $field=>$data)
    {
        $body.=urlencode("<div>$field: <b>$data</b></div>");
        //$data=urlencode($data);
        if($_REQUEST['lang']=="cz" AND $field=="dn"){$data=date2cz($data);}
        if($field<>"id" AND $field<>"mode" ){$req.="$field=$data&";}
    }

    $sql="SELECT * FROM $db_study_programs WHERE id=".$_REQUEST['study'];
    $data=$db->query($sql);$data -> execute();$result = $data -> fetch();
    foreach($result as $field=>$data)
    {
        $data=urlencode($data);
        if($field<>"id" AND $field<>"mode" ){$req2.="$field=$data&";}
    }

    if($_REQUEST['currency']=="CZK"){$price=$result['czk'];$curr="CZK";}
    if($_REQUEST['currency']=="€"){$price=$result['eur'];$curr="EUR";}

    $lang=$_REQUEST['lang'];

    $body.=urlencode("<div>Student no.: <b>$sid</b></div>");

/*    
    $mail = new PHPMailer(true);
        
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        //Server settings
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.seznam.cz';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'system@europeanacademy.online';                     //SMTP username
        $mail->Password   = 'EA2023,,';                               //SMTP password
        $mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    
        $mail->isHTML(true);
        $mail->setFrom('system@europeanacademy.online', $txtacademy );
        $mail->addAddress("info@europeanacademy.cz");     //Add a recipient
        $mail->AddReplyTo($_REQUEST['email']);
        $mail->Subject = "New student $sid $studentname";
        $mail->Body    = $body;
        $mail->send();
*/

/*
    $mail = new PHPMailer(true);
        
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        //Server settings
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.seznam.cz';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'system@europeanacademy.online';                     //SMTP username
        $mail->Password   = 'EA2023,,';                               //SMTP password EA2023,,
        $mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //$mail->Username   = 'info@europeanacademy.cz';                     //SMTP username
        //$mail->Password   = 'EA2022..';                               //SMTP password
        //$mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
        //$mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    
        $mail->isHTML(true);
        $mail->setFrom('system@europeanacademy.online', $txtacademy );
        $mail->addAddress($_REQUEST['email']);     //Add a recipient
        $mail->addBCC("info@europeanacademy.cz");
        $mail->Subject = "$txtregsubject";
        
        $mail->AddEmbeddedImage("./logo-$lang.png", "logo");

        if($_REQUEST['study']<100){ //profesní studium        
          
          $mail->AddAttachment($tmpcon=tempfile("https://prace.estudium.eu/app/study/document.pdf.php?id=4001&lang=$lang&$req&$req2&sid=$sid&price=$price") , "$txtfilecontract.pdf");
          $mail->AddAttachment(tempfile("https://prace.estudium.eu/app/study/document.pdf.php?id=5001&lang=$lang") , "$txtfilecode.pdf");
          $mail->AddAttachment(tempfile("https://prace.estudium.eu/app/study/document.pdf.php?id=5003&lang=$lang") , "$txtfileregulations.pdf");
          
          $txtemailconditionPS=tagset($result,$txtemailconditionPS);
          $mail->Body    = $txtregemail.$txtemailconditionPS.$txtemailsig."<div><br><img width=350 src='cid:logo' ></div>";
        }
        else //profesní kvalifikace
        {
        
          if($_REQUEST['study']>109 AND $_REQUEST['study']<116){$mail->AddAttachment(tempfile("https://prace.estudium.eu/app/study/document.pdf.php?id=4003&lang=cz") , "Potvrzení o zdravotní způsobilosti.pdf");}
          $mail->AddAttachment($tmpcon=tempfile("https://prace.estudium.eu/app/study/document.pdf.php?id=4002&lang=$lang&$req&$req2&sid=$sid&price=$price") , "$txtfilecontractpk.pdf");
          
          $txtemailconditionPK=tagset($result,$txtemailconditionPK);
          $mail->Body    = $txtregemail.$txtemailconditionPK.$txtemailsig."<div><br><img width=350 src='cid:logo' ></div>";
        
        
        }
        
        $mail->send();
*/        
        //print $req;
        //print $req2;
        //print "<br>https://prace.estudium.eu/app/study/document.pdf.php?id=4001&lang=$lang&$req&sid=$sid";

    $sql="SELECT * FROM $db_study_programs WHERE id='".$_REQUEST['study']."' LIMIT 1;";
    $data=$db->query($sql);//$data -> execute();
    $result = $data -> fetch();
    
    //print_r($result);

    

    $sql="INSERT INTO $db_students (id,titul1,titul2,firstname,surname,email,addr1,addr2,city,zip,country,tel,lang,dn,rc,bornplace,bornplacecountry,eqf,active,ico,company,bck_email,ccountry,gender,firstname_p,surname_p,dn_p) VALUES($sid,'".$_REQUEST['titul1']."','".$_REQUEST['titul2']."','".$_REQUEST['firstname']."','".$_REQUEST['surname']."','".$_REQUEST['email']."','".$_REQUEST['addr1']."','".$_REQUEST['addr2']."','".$_REQUEST['city']."','".$_REQUEST['zip']."','".$_REQUEST['country']."','".$_REQUEST['tel']."','".$_REQUEST['lang']."','".$_REQUEST['dn']."','".$_REQUEST['rc']."','".$_REQUEST['bornplace']."','".$_REQUEST['bornplacecountry']."','".$_REQUEST['eqf']."','0','".$_REQUEST['ico']."','".$_REQUEST['company']."','".$_REQUEST['bck_email']."','".$_REQUEST['ccountry']."','".$_REQUEST['gender']."','".$_REQUEST['firstname_p']."','".$_REQUEST['surname_p']."','".$_REQUEST['dn_p']."');";
    $data=$db->query($sql);

    $sale=strtolower(trim($_REQUEST[sale]) );
    if($sale=="praha10"){$price=sale($price,10,$curr);}
    if($sale=="brno10"){$price=sale($price,10,$curr);}
    if($sale=="morava10"){$price=sale($price,10,$curr);}
    
    $sql="INSERT INTO $db_students_programs (sid,study_pgm,lang,price,currency,active,studyform) VALUES ($sid,'".$_REQUEST['study']."','".$_REQUEST['lang']."','$price','$curr',0,'".$_REQUEST['studyform']."');";
    $data=$db->query($sql);

      if($_REQUEST[spl]){$bccs="bcc=finance@europeanacademy.cz";}
      $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=new&mode=info&body=$body&$bccs";
      $content = file_get_contents($url);

/*
    if($_REQUEST['study']>100){
      $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=registrationPK&bcc=info@europeanacademy.cz";
    }
    else{
      $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=registrationPS&bcc=info@europeanacademy.cz";
    }

*/

    if($_REQUEST['study'] >= 10 && $_REQUEST['study'] <= 49) {
      $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=registrationSOS&bcc=info@europeanacademy.cz";
    } 
    
    elseif($_REQUEST['study'] >= 60 && $_REQUEST['study'] <= 99) {
      $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=registrationPS&bcc=info@europeanacademy.cz";
    } 
    
    else {
      $url="https://prace.estudium.eu/app/study/mailing.inc.php?key=$currentDate&student=$sid&id=registrationPK&bcc=info@europeanacademy.cz";
    }


      $content = file_get_contents($url);



}

?>
</body>
</html>
