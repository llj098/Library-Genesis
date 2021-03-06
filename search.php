<?php
	include 'connect.php';
	include 'html.php';
	include 'strings.php';
	include 'util.php';


	if (sizeof($_GET)) $mainpage = false;
	else $mainpage = true;

	// SQL-requests should encode single-quotes and underscores with Esc-sequences
	if (!$mainpage){
		$req = addcslashes(mysql_real_escape_string($_GET['req']),"%_");
		if (strlen($req) < 1) die($htmlhead."<font color='#A00000'><h1>Wrong Request</h1></font>Search string must contain more than one character.<br>Please, type in a longer request and <a href=>try again</a>.".$htmlfoot);

		$req_htm = htmlspecialchars($_GET['req'],ENT_QUOTES);
		$req_htm_enc = urlencode($_GET['req']);
        if( isset($_GET['nametype'])) $dlnametype = $_GET['nametype'];
        else $dlnametype = "md5"; //
	} else {
		$req_htm = "";
        $dlnametype = "orig";
	}

			


	$footer = "</tr></table>\n";

    $dlnametypes = array('orig' => '',
                         'md5' => '',
                         'translit' => ''
    );
    
    foreach( $dlnametypes as $key => $value ) {
        if ( $key == $dlnametype ) {
            $dlnametypes[$key] = 'checked';
        } else {
            $dlnametypes[$key] = '';
        }
    }
    
	$form = "<form name ='myform' action='search'><br>
	<input name=req id='searchform' size=60 maxlength=80 value='$req_htm'><input type=submit value='Search!'><br>
    <label><b>Download name as:</b></label>
    <input type=radio name='nametype' id='orig' value='orig' ".$dlnametypes['orig']." onclick=radioOnClick('orig') />
    <label for='Original'>Original</label>
    <input type=radio name='nametype' id='md5' value='md5' ".$dlnametypes['md5']." onclick=radioOnClick('md5') />
    <label for='Md5'>Md5</label><br>
 <form action method='get'>
<font><b>Search in fields:</b></font><input type='checkbox' name='column[]' value='title' checked=true>Title <input type='checkbox' name='column[]' value='author' checked=true>Autor<input type='checkbox' name='column[]' value='publisher'>Publisher <input type='checkbox' name='column[]' value='Identifier'>ISBN <br>
<input type='checkbox' name='column[]' value='language'>Language <input type='checkbox' name='column[]' value='year'>Year<input type='checkbox' name='column[]' value='md5'>MD5 <input type='checkbox' name='column[]' value='series'>Series <input type='checkbox' name='column[]' value='extension'>Extension <input type='checkbox' name='column[]' value='topic'>Topic
</form>
    	</form>";

if(isset($_GET['column'])){
  $column = $_GET['column'];
  $fieldslist = implode(',',$column);
}else{
  $fieldslist = 'Title,Author';
}



          echo $htmlheadfocus;
          include 'menu.html';
          include 'stats.php';


	// if no arguments passed, give out the main page
	if ($mainpage) {
		$searchbody = "<table cellspacing=0 width=100% height=100%>
		<tr><td height=27% width=35% valign=top align=left></td><td></td><td width=35% valign=top align=right></td></tr>
		<tr height=34%><td></td><td><center><table><tr><caption><font color=red><h1>Library Genesis<sup><font size=4><img src='http://gen.lib.rus.ec/wiki/images/math/f/8/5/f8577a96a48c2f06d7633a9a9ade5320.png'></font></sup></h1></font></caption><td nowrap>{$form}</td></tr></table></center></td></tr>
		<tr><td width=25% valign=bottom align=left></td><td></td><td width=25% valign=bottom align=right></td>";

		//echo $toolbar;
		echo $searchbody;
		echo $footer;
		echo $htmlfoot;
		die;
	}

	// now look up in the database
        $errurl = 'http://gen.lib.rus.ec/forum/viewtopic.php?f=3&t=210';
	$dberr = $htmlhead."<font color='#A00000'><h1>Error</h1></font>".mysql_error()."<br>Cannot proceed.<p>Please, <a href='{$errurl}'><u>report</u></a> on this error.".$htmlfoot;

	if (isset($_GET['lines'])) $lines = $_GET['lines'];
	else $lines = $maxlines;

	// reset to deafult $maxlines, if wrong
	if ($lines > $maxlines || $lines <= 0) $lines = $maxlines;

	if (isset($_GET['lines'])) $from = $_GET['from'];
	else $from = 0;

	if ($from < $maxlines - $lines) $from = 0;



	$sql_end = " ORDER BY Title LIMIT $from, $lines";
	$search_words = explode(' ', $req);
        $search_fields = "CONCAT({$fieldslist}) LIKE '%";
	$search_core = $search_fields.implode("%' AND $search_fields", $search_words)."%'";
	$search_isbn = "Identifier LIKE '%$req%'";
	$sql_mid = "FROM $dbtable WHERE ((($search_core) OR $search_isbn) AND Filename!='' AND Generic='' AND Visible='')";
	$sql_req = "SELECT * ".$sql_mid.$sql_end;
	$sql_cnt = "SELECT SUM(Filesize), COUNT(*) ".$sql_mid;

	$result = mysql_query($sql_cnt,$con);
	if (!$result) die($dberr);

	$row = mysql_fetch_assoc($result);
	$totalrows = stripslashes($row['COUNT(*)']);
	$totalsize = stripslashes($row['SUM(Filesize)']);
	if ($totalsize >= 1024*1024*1024){
        	$totalsize = round($totalsize/1024/1024/1024);
		$totalsize = $totalsize.' GB';
		} else
		if ($totalsize >= 1024*1024){
			$totalsize = round($totalsize/1024/1024);
			$totalsize = $totalsize.' MB';
		} else
		if ($totalsize >= 1024){
			$totalsize = round($totalsize/1024);
			$totalsize = $totalsize.' kB';
		} else
			$totalsize = $totalsize.' B';

	mysql_free_result($result);

	$result = mysql_query($sql_req,$con);
	if (!$result) die($dberr);

	///////////////////////////////////////////////////////////////
	// pagination




	$args = "search?nametype=$dlnametype&req=$req_htm_enc&lines=$lines";
foreach ($column as $col){
$args .= "&column[]=$col";
} 

	if ($totalrows > $from + $lines){
		$nextpage = $from + $lines;
		$argsnext = $args."&from=".$nextpage;
		$nextlink1 = "<a href='$argsnext' id='nextlink1'>".$str_next."</a>";
		$nextlink2 = "<a href='$argsnext' id='nextlink2'>".$str_next."</a>";
        $linesOnPage = $lines;
	} else {
		$nextpage = 0;
		$nextlink1 = $str_next;
		$nextlink2 = $str_next;
        $linesOnPage = $totalrows-$from;
	}

	if ($from > 0) {
		$prevpage = $from - $lines;
		if ($prevpage < 0) $prevpage = 0;
		$argsprev = $args."&from=".$prevpage;
		$prevlink1 = "<a href='$argsprev' id='prevlink1'>".$str_prev."</a>";
		$prevlink2 = "<a href='$argsprev' id='prevlink2'>".$str_prev."</a>";
	} else {
		$prevlink1 = $str_prev;
		$prevlink2 = $str_prev;
	}
    
    $onClickScript = "<script type='text/javascript'>
    function radioOnClick(txt) 
    {
        for (var i=$from+1;i<$from+$linesOnPage+1;i++) {
            changeQuery(document.getElementById(i),txt);
        }
        
        changeQuery(document.getElementById('prevlink1'),txt);
        changeQuery(document.getElementById('prevlink2'),txt);
        changeQuery(document.getElementById('nextlink1'),txt);
        changeQuery(document.getElementById('nextlink2'),txt);
    }
    
    function changeQuery(obj,txt) {
        if (!obj) return;
        var query = obj.href;
        var vars = query.split('&');
        var pair = vars[0].split('=');
        obj.href = pair[0] + '=' + txt;
        
        for(var i=1;i<vars.length;i++) {
            obj.href = obj.href + '&' + vars[i];
        }
    }
    
    function forceDlNameTypeSwitch() {
        var x=document.myform.nametype;
        if (!x) return;
        for(var i=0;i<x.length;i++) {
            if (x[i].value == '$dlnametype') {
                x[i].checked = 'true';
            } 
        }
    }
    
    // When 'Refresh' is clicked, Firefox refuses to change radiobutton
    // back to the one, which was chosen originally, which leads to
    // broken synchronization between radiobuttons and links on the page,
    // so wee need to force switch radiobuttons to the correct state.
    // Other browsers seem to react nicely to this, too.    
    forceDlNameTypeSwitch();
    </script>";
    
//	$reshead = "<table width=100% cellspacing=0 cellpadding=0 border=1 class=c align=center>";
	$reshead = "<table width=100% cellspacing=1 cellpadding=1 rules=rows class=c align=center>";

	if (!$mainpage){
echo "<table width=100%><tr><td>$form</td><td><font color=red valign=top align=right><h1>Library Genesis<sup><font size=4><img src='http://gen.lib.rus.ec/wiki/images/math/f/8/5/f8577a96a48c2f06d7633a9a9ade5320.png'></font></sup></h1></font></td></tr></table>";
}
	echo $reshead;
        // echo $googletrans;

	$color1 = '#D0D0D0';
//	$color2 = '#F6F6FF';
	$color2 = '#F0F5FE';
	$color3 = '#000000';

	echo "\n<b>".$totalsize."\t,\t".$totalrows." pieces found for <u>$req_htm</u> </b>\n";
	$navigatortop = "<tr><th valign=top bgcolor=$color1 colspan=15><font color=$color1><center><b>$prevlink1 | $nextlink1</b></center></font></th></tr>";
	$navigatorbottom = "<tr><th valign=top bgcolor=$color1 colspan=15><font color=$color1><center><b>$prevlink2 | $nextlink2</b></center></font></th></tr>";
	$tabheader = "<tr valign=top bgcolor=$color2><td><b>#</b></td><td><b>Author</b></td><td><b>Title</b></td><td><b>Publisher</b></td><td><b>Year</b></td><td><b>Pp</b></td><td><b>Lang.</b></td><td><b>Size</b></td><td><b>Type</b></td><td colspan=3><b>Mirrors</b></td><td><b>Edit</b></td></tr>";
	echo $navigatortop;
	echo $tabheader;

	//$repository = str_replace('\\','/',realpath($repository));

	$i = 1;
	while ($row = mysql_fetch_assoc($result)){
		$title = stripslashes($row['Title']);
		$author = stripslashes($row['Author']);
		$vol = stripslashes($row['VolumeInfo']);
		$publisher = stripslashes($row['Publisher']);
		$year = $row['Year'];
		$pages = $row['Pages'];
                $periodical = stripslashes($row['Periodical']);
                $series = stripslashes($row['Series']);
		$lang = stripslashes($row['Language']);
		$ident1 = stripslashes($row['Identifier']);
		$edition = stripslashes($row['Edition']);
		$ext = stripslashes($row['Extension']);
		$library = stripslashes($row['Library']);
        $filename = stripslashes($row['Filename']);
$ident = ereg_replace("ISBN", " ISBN", $ident1);
        
        $bookname = '';
        if ($series <> '') {
            $bookname = "<font face=Times color=green><i>($series) </i></font>";
        }
        
        $bookname1 = '';
        if ($periodical <> '') {
            $bookname1 = "<font face=Times color=Red><i>$periodical </i></font>";
        }

        $bookname = $bookname1.$bookname.$title;


		$size = $row['Filesize'];
		if ($size >= 1024*1024*1024){
			$size = round($size/1024/1024/1024);
			$size = $size.' GB';
		} else
		if ($size >= 1024*1024){
			$size = round($size/1024/1024);
			$size = $size.' MB';
		} else
		if ($size >= 1024){
			$size = round($size/1024);
			$size = $size.' kB';
		} else
			$size = $size.' B';

		///////////
		// book info section (in parentheses)
		$volinf = $ident;

		if ($volinf){
			if ($ident) $volinf = $ident;
		} else {
			if ($ident) $volinf = ''.$ident;
		}
		///////////
		// output
		if ($i % 2) $color = ""; // $color1
		else $color = $color2;

		$vol_ed = $vol;
		if ($lang == 'Russian') $ed = ' '.$str_edition_ru;
		else $ed = ' '.$str_edition_en;
		if ($vol_ed){
			if ($edition) $vol_ed = $vol_ed.', '.$edition.$ed;
		} else {
			if ($edition) $vol_ed = $edition.$ed;
		}

		$volume = '';
		if ($vol_ed) $volume = " <font face=Times color=green><i>[$vol_ed]</i></font>";

		$volstamp = '';
		if ($volinf) $volstamp = " <font face=Times color=green><i>($volinf)</i></font>";

		$ires = $from + $i;

		//$tipdir = str_replace($row['MD5'],'',$filename,$count); //echo $count;
        list($tipdir,$file) = split($filesep,$filename);
		if ($library) $tiplib = 'Library: '.$library."\n";
		else $tiplib = '';


        if ($row['ID'] > 215000){
                $path = "genesis2";
        }else{$path = "genesis1";
          }


        $repdir = str_replace('\\','/',realpath(getRepDirByFilename($filename)));
		$tip = "ID: $row[ID]; $tiplib; Location: $repdir/$tipdir";
		$tip1 = "Login-Password look at the forum";
		$tip3 = "Download from free-books.dontexist.com";
		$tip4 = "Download from bookfi.org";
		$tip5 = "Download from gen.lib.rus.ec";
		$line = "<tr valign=top bgcolor=$color><td>$ires</td>
		<td>$author</td>
		<td width=500><a href='book/index.php?md5=$row[MD5]'title='$tip' id=$ires>{$bookname}$volume$volstamp</a></td>
		<td>$publisher</td>
		<td nowrap>$year</td>
		<td nowrap>$pages</td>
		<td nowrap>$lang</td>
		<td nowrap>$size</td>
		<td nowrap>$ext</td>


            



		<td><a href='http://proxy.bookfi.org/$path/$row[Filename]/_as/$row[Author]_$row[Title]($row[Year]).$row[Extension]'title='$tip4'><b>[dl1]</b></a></td>
		<td><a href='http://free-books.dontexist.com/get?nametype=$dlnametype&md5=$row[MD5]'title='$tip3'>[dl2]</a></td>
		<td><a href='http://gen.lib.rus.ec/get?nametype=$dlnametype&md5=$row[MD5]'title='$tip5'>[dl3]</a></td>
		<td><a href='http://free-books.dontexist.com/librarian/registration?md5=$row[MD5]'title='$tip1'>[edit]</a></td>
		</tr>\n\n";

		echo $line;
		$i = $i + 1;
	}

    echo $onClickScript;
    
	echo $navigatorbottom;
	echo $footer;
	echo $htmlfoot;

	mysql_free_result($result);
	mysql_close($con);

?>

