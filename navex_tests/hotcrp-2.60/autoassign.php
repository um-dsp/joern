<?php
// autoassign.php -- HotCRP automatic paper assignment page
// HotCRP is Copyright (c) 2006-2012 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

require_once("Code/header.inc");
require_once("Code/paperlist.inc");
require_once("Code/search.inc");
$Me->goIfInvalid();
$Me->goIfNotPrivChair();

// paper selection
if (!isset($_REQUEST["q"]) || trim($_REQUEST["q"]) == "(All)")
    $_REQUEST["q"] = "";
if (isset($_REQUEST["pcs"]) && is_string($_REQUEST["pcs"]))
    $_REQUEST["pcs"] = preg_split('/\s+/', $_REQUEST["pcs"]);
if (isset($_REQUEST["pcs"]) && is_array($_REQUEST["pcs"])) {
    $pcsel = array();
    foreach ($_REQUEST["pcs"] as $p)
	if (($p = cvtint($p)) > 0)
	    $pcsel[$p] = 1;
} else
    $pcsel = pcMembers();
if (defval($_REQUEST, "a") == "prefconflict" && !isset($_REQUEST["t"])
    && $Conf->setting("pc_seeall") > 0)
    $_REQUEST["t"] = "all";
else if ($Conf->has_managed_submissions())
    $_REQUEST["t"] = defval($_REQUEST, "t", "unm");
else
    $_REQUEST["t"] = defval($_REQUEST, "t", "s");
if (!isset($_REQUEST["p"]) && isset($_REQUEST["pap"]))
    $_REQUEST["p"] = $_REQUEST["pap"];
if (isset($_REQUEST["p"]) && is_string($_REQUEST["p"]))
    $_REQUEST["p"] = preg_split('/\s+/', $_REQUEST["p"]);
if (isset($_REQUEST["p"]) && is_array($_REQUEST["p"]) && !isset($_REQUEST["requery"])) {
    $papersel = array();
    foreach ($_REQUEST["p"] as $p)
	if (($p = cvtint($p)) > 0)
	    $papersel[] = $p;
} else {
    $papersel = array();
    $search = new PaperSearch($Me, array("t" => $_REQUEST["t"], "q" => $_REQUEST["q"]));
    $papersel = $search->paperList();
}
sort($papersel);
if ((isset($_REQUEST["prevt"]) && isset($_REQUEST["t"]) && $_REQUEST["prevt"] != $_REQUEST["t"])
    || (isset($_REQUEST["prevq"]) && isset($_REQUEST["q"]) && $_REQUEST["prevq"] != $_REQUEST["q"])) {
    if (isset($_REQUEST["p"]) && isset($_REQUEST["assign"]))
	$Conf->infoMsg("You changed the paper search.  Please review the resulting paper list.");
    unset($_REQUEST["assign"]);
    $_REQUEST["requery"] = 1;
}
if (!isset($_REQUEST["assign"]) && !isset($_REQUEST["requery"])
    && isset($_REQUEST["default"]) && isset($_REQUEST["defaultact"])
    && ($_REQUEST["defaultact"] == "assign" || $_REQUEST["defaultact"] == "requery"))
    $_REQUEST[$_REQUEST["defaultact"]] = true;
if (!isset($_REQUEST["pctyp"]) || ($_REQUEST["pctyp"] != "all" && $_REQUEST["pctyp"] != "sel"))
    $_REQUEST["pctyp"] = "all";

// bad pairs
// load defaults from last autoassignment or save entry to default
$pcm = pcMembers();
if (!isset($_REQUEST["bpcount"]) || !ctype_digit($_REQUEST["bpcount"]))
    $_REQUEST["bpcount"] = "50";
if (!isset($_REQUEST["badpairs"]) && !isset($_REQUEST["assign"]) && !count($_POST)) {
    $x = preg_split('/\s+/', $Conf->settingText("autoassign_badpairs", ""), null, PREG_SPLIT_NO_EMPTY);
    $bpnum = 1;
    for ($i = 0; $i < count($x) - 1; $i += 2)
	if (isset($pcm[$x[$i]]) && isset($pcm[$x[$i+1]])) {
	    $_REQUEST["bpa$bpnum"] = $x[$i];
	    $_REQUEST["bpb$bpnum"] = $x[$i+1];
	    ++$bpnum;
	}
    $_REQUEST["bpcount"] = $bpnum - 1;
    if ($Conf->setting("autoassign_badpairs"))
	$_REQUEST["badpairs"] = 1;
} else if (count($_POST) && isset($_REQUEST["assign"]) && check_post()) {
    $x = array();
    for ($i = 1; $i <= $_REQUEST["bpcount"]; ++$i)
	if (defval($_REQUEST, "bpa$i") && defval($_REQUEST, "bpb$i")
	    && isset($pcm[$_REQUEST["bpa$i"]]) && isset($pcm[$_REQUEST["bpb$i"]])) {
	    $x[] = $_REQUEST["bpa$i"];
	    $x[] = $_REQUEST["bpb$i"];
	}
    if (count($x) || $Conf->settingText("autoassign_badpairs")
	|| (!isset($_REQUEST["badpairs"]) != !$Conf->setting("autoassign_badpairs")))
	$Conf->q("insert into Settings (name, value, data) values ('autoassign_badpairs', " . (isset($_REQUEST["badpairs"]) ? 1 : 0) . ", '" . sqlq(join(" ", $x)) . "') on duplicate key update data=values(data), value=values(value)");
}
// set $badpairs array
$badpairs = array();
if (isset($_REQUEST["badpairs"]))
    for ($i = 1; $i <= $_REQUEST["bpcount"]; ++$i)
	if (defval($_REQUEST, "bpa$i") && defval($_REQUEST, "bpb$i")) {
	    if (!isset($badpairs[$_REQUEST["bpa$i"]]))
		$badpairs[$_REQUEST["bpa$i"]] = array();
	    if (!isset($badpairs[$_REQUEST["bpb$i"]]))
		$badpairs[$_REQUEST["bpb$i"]] = array();
	    $badpairs[$_REQUEST["bpa$i"]][$_REQUEST["bpb$i"]] = 1;
	    $badpairs[$_REQUEST["bpb$i"]][$_REQUEST["bpa$i"]] = 1;
	}

// score selector
$scoreselector = array();
$rf = reviewForm();
if ($rf->field("overAllMerit")->displayed) { // overAllMerit comes first
    $scoreselector["+overAllMerit"] = "";
    $scoreselector["-overAllMerit"] = "";
}
foreach ($rf->forder as $f)
    if ($f->has_options) {
	$scoreselector["+" . $f->id] = "high $f->name_html scores";
	$scoreselector["-" . $f->id] = "low $f->name_html scores";
    }
$scoreselector["x"] = "(no score preference)";

$Error = array();


function countReviews() {
    global $Conf, $papersel;
    $nrev = (object) array("any" => array(), "pri" => array(), "sec" => array());
    $nrev->pset = (object) array("any" => array(), "pri" => array(), "sec" => array());
    foreach (pcMembers() as $id => $pc) {
	$nrev->any[$id] = $nrev->pri[$id] = $nrev->sec[$id] = 0;
	$nrev->pset->any[$id] = $nrev->pset->pri[$id] = $nrev->pset->sec[$id] = 0;
    }

    $result = $Conf->qe("select PC.contactId, group_concat(R.reviewType separator ''),
		group_concat(R.reviewType separator '')
	from PCMember PC
	left join PaperReview R on (R.contactId=PC.contactId)
	left join Paper P on (P.paperId=R.paperId)
	where P.paperId is null or P.timeWithdrawn<=0
	group by PC.contactId", "while counting reviews");
    while (($row = edb_row($result))) {
	$nrev->any[$row[0]] = strlen($row[1]);
	$nrev->pri[$row[0]] = preg_match_all("|" . REVIEW_PRIMARY . "|", $row[1], $matches);
	$nrev->sec[$row[0]] = preg_match_all("|" . REVIEW_SECONDARY . "|", $row[1], $matches);
    }

    $result = $Conf->qe("select PC.contactId, group_concat(R.reviewType separator ''),
		group_concat(R.reviewType separator '')
	from PCMember PC
	left join PaperReview R on (R.contactId=PC.contactId)
	where R.paperId" . PaperSearch::sqlNumericSet($papersel) . "
	group by PC.contactId", "while counting reviews");
    while (($row = edb_row($result))) {
	$nrev->pset->any[$row[0]] = strlen($row[1]);
	$nrev->pset->pri[$row[0]] = preg_match_all("|" . REVIEW_PRIMARY . "|", $row[1], $matches);
	$nrev->pset->sec[$row[0]] = preg_match_all("|" . REVIEW_SECONDARY . "|", $row[1], $matches);
    }

    return $nrev;
}

function _review_count_link($count, $word, $pl, $prefix, $pc, $suffix) {
    $word = $pl ? plural($count, $word) : $count . "&nbsp;" . $word;
    if ($count == 0)
	return $word;
    return "<a class=\"qq\" href=\"" . hoturl("search", "q=" . urlencode("$prefix:$pc->email$suffix"))
	. "\">" . $word . "</a>";
}

function _review_count_report_one($nrev, $pc, $xq) {
    $na = defval($nrev->any, $pc->contactId, 0);
    $np = defval($nrev->pri, $pc->contactId, 0);
    $ns = defval($nrev->sec, $pc->contactId, 0);
    $t = _review_count_link($na, "review", true, "re", $pc, $xq);
    $x = array();
    if ($np != $na)
	$x[] = _review_count_link($np, "primary", false, "pri", $pc, $xq);
    if ($ns != 0 && $ns != $na && $np + $ns != $na)
	$x[] = _review_count_link($np, "secondary", false, "sec", $pc, $xq);
    if (count($x))
	$t .= " (" . join(", ", $x) . ")";
    return $t;
}

function review_count_report($nrev, $pc, $prefix) {
    global $papersel, $Conf;
    $row1 = _review_count_report_one($nrev, $pc, "");
    if (defval($nrev->pset->any, $pc->contactId, 0) != defval($nrev->any, $pc->contactId, 0)) {
	$row2 = "<span class=\"dim\">$row1 total</span>";
	$row1 = _review_count_report_one($nrev->pset, $pc, " " . join(" ", $papersel)) . " in selection";
    } else
	$row2 = "";
    if ($row2 != "" && $prefix)
	return "<table><tr><td>$prefix</td><td>$row1</td></tr><tr><td></td><td>$row2</td></tr></table>";
    else if ($row2 != "")
	return $row1 . "<br />" . $row2;
    else
	return $prefix . $row1;
}

function conflictedPapers() {
    global $Conf, $Me;
    if ($Conf->sversion >= 51)
        $result = $Conf->qe("select p.paperId, p.managerContactId from Paper p join PaperConflict c on (c.paperId=p.paperId) where c.conflictType!=0 and c.contactId=$Me->cid");
    else
        $result = $Conf->qe("select paperId, 0 from PaperConflict where conflictType!=0 and contactId=$Me->cid");
    $confs = array();
    while (($row = edb_row($result)))
	$confs[$row[0]] = $row[1];
    return $confs;
}

if (!function_exists("array_fill_keys")) {
    function array_fill_keys($a, $v) {
	$x = array();
	foreach ($a as $k)
	    $x[$k] = $v;
	return $x;
    }
}

function checkRequest(&$atype, &$reviewtype, $save) {
    global $Error, $Conf;

    $atype = $_REQUEST["a"];
    $atype_review = ($atype == "rev" || $atype == "revadd" || $atype == "revpc");
    if (!$atype_review && $atype != "lead" && $atype != "shepherd"
	&& $atype != "prefconflict" && $atype != "clear") {
	$Error["ass"] = true;
	return $Conf->errorMsg("Malformed request!");
    }

    if ($atype_review) {
	$reviewtype = defval($_REQUEST, $atype . "type", "");
	if ($reviewtype != REVIEW_PRIMARY && $reviewtype != REVIEW_SECONDARY
	    && $reviewtype != REVIEW_PC) {
	    $Error["ass"] = true;
	    return $Conf->errorMsg("Malformed request!");
	}
    }
    if ($atype == "clear")
	$reviewtype = defval($_REQUEST, "cleartype", "");
    if ($atype == "clear"
	&& ($reviewtype != REVIEW_PRIMARY && $reviewtype != REVIEW_SECONDARY
	    && $reviewtype != REVIEW_PC
	    && $reviewtype !== "conflict" && $reviewtype !== "lead"
	    && $reviewtype !== "shepherd")) {
	$Error["clear"] = true;
	return $Conf->errorMsg("Malformed request!");
    }
    $_REQUEST["rev_roundtag"] = defval($_REQUEST, "rev_roundtag", "");
    if ($_REQUEST["rev_roundtag"] == "(None)")
	$_REQUEST["rev_roundtag"] = "";
    if ($atype_review && $_REQUEST["rev_roundtag"] != ""
	&& !preg_match('/^[a-zA-Z0-9]+$/', $_REQUEST["rev_roundtag"])) {
	$Error["rev_roundtag"] = true;
	return $Conf->errorMsg("The review round must contain only letters and numbers.");
    }

    if ($save)
	/* no check */;
    else if ($atype == "rev" && rcvtint($_REQUEST["revct"], -1) <= 0) {
	$Error["rev"] = true;
	return $Conf->errorMsg("Enter the number of reviews you want to assign.");
    } else if ($atype == "revadd" && rcvtint($_REQUEST["revaddct"], -1) <= 0) {
	$Error["revadd"] = true;
	return $Conf->errorMsg("You must assign at least one review.");
    } else if ($atype == "revpc" && rcvtint($_REQUEST["revpcct"], -1) <= 0) {
	$Error["revpc"] = true;
	return $Conf->errorMsg("You must assign at least one review.");
    }

    return true;
}

function noBadPair($pc, $pid, $prefs) {
    global $badpairs;
    foreach ($badpairs[$pc] as $opc => $val)
	if (defval($prefs[$opc], $pid, 0) < -1000000)
	    return false;
    return true;
}

function doAssign() {
    global $Conf, $papersel, $pcsel, $assignments, $assignprefs, $badpairs, $scoreselector;

    // check request
    if (!checkRequest($atype, $reviewtype, false))
	return false;

    // fetch PC members, initialize preferences and results arrays
    $pcm = pcMembers();
    $prefs = array();
    foreach ($pcm as $pc)
	$prefs[$pc->contactId] = array();
    $assignments = array();
    $assignprefs = array();

    // choose PC members to use for assignment
    if ($_REQUEST["pctyp"] == "sel") {
	$pck = array_keys($pcm);
	foreach ($pck as $pcid)
	    if (!isset($pcsel[$pcid]))
		unset($pcm[$pcid]);
	if (!count($pcm)) {
	    $Conf->errorMsg("Select one or more PC members to assign.");
	    return null;
	}
    }

    // prefconflict is a special case
    if ($atype == "prefconflict") {
	$papers = array_fill_keys($papersel, 1);
	$result = $Conf->qe($Conf->preferenceConflictQuery($_REQUEST["t"], ""), "while fetching preferences");
	while (($row = edb_row($result))) {
	    if (!isset($papers[$row[0]]) || !isset($pcm[$row[1]]))
		continue;
	    if (!isset($assignments[$row[0]]))
		$assignments[$row[0]] = array();
	    $assignments[$row[0]][] = $row[1];
	    $assignprefs["$row[0]:$row[1]"] = $row[2];
	}
	if (count($assignments) == 0) {
	    $Conf->warnMsg("Nothing to assign.");
	    unset($assignments);
	}
	return;
    }

    // clear is another special case
    if ($atype == "clear") {
	$papers = array_fill_keys($papersel, 1);
	if ($reviewtype == REVIEW_PRIMARY || $reviewtype == REVIEW_SECONDARY
	    || $reviewtype == REVIEW_PC)
	    $q = "select paperId, contactId from PaperReview where reviewType=" . $reviewtype;
	else if ($reviewtype === "conflict")
	    $q = "select paperId, contactId from PaperConflict where conflictType>0 and conflictType<" . CONFLICT_AUTHOR;
	else if ($reviewtype === "lead" || $reviewtype === "shepherd")
	    $q = "select paperId, ${reviewtype}ContactId from Paper where ${reviewtype}ContactId!=0";
	$result = $Conf->qe($q, "while checking clearable assignments");
	while (($row = edb_row($result))) {
	    if (!isset($papers[$row[0]]) || !isset($pcm[$row[1]]))
		continue;
	    if (!isset($assignments[$row[0]]))
		$assignments[$row[0]] = array();
	    $assignments[$row[0]][] = $row[1];
	    $assignprefs["$row[0]:$row[1]"] = "X";
	}
	if (count($assignments) == 0) {
	    $Conf->warnMsg("Nothing to assign.");
	    unset($assignments);
	}
	return;
    }

    // prepare to balance load
    $load = array_fill_keys(array_keys($pcm), 0);
    if (defval($_REQUEST, "balance", "new") != "new" && $atype != "revpc") {
	if ($atype == "rev" || $atype == "revadd")
	    $result = $Conf->qe("select PCMember.contactId, count(reviewId)
		from PCMember left join PaperReview on (PaperReview.contactId=PCMember.contactId and PaperReview.reviewType=$reviewtype)
		group by PCMember.contactId", "while counting reviews");
	else
	    $result = $Conf->qe("select PCMember.contactId, count(paperId)
		from PCMember left join Paper on (Paper.${atype}ContactId=PCMember.contactId)
		where not (paperId in (" . join(",", $papersel) . "))
		group by PCMember.contactId", "while counting leads");
	while (($row = edb_row($result)))
	    $load[$row[0]] = $row[1] + 0;
    }

    // get preferences
    if (($atype == "lead" || $atype == "shepherd")
	&& isset($_REQUEST["${atype}score"])
	&& isset($scoreselector[$_REQUEST["${atype}score"]])) {
	$score = $_REQUEST["${atype}score"];
	if ($score == "x")
	    $score = "1";
	else
	    $score = "PaperReview." . substr($score, 1);
    } else
	$score = "PaperReview.overAllMerit";
    $result = $Conf->qe("select Paper.paperId, PCMember.contactId,
	coalesce(PaperConflict.conflictType, 0) as conflictType,
	coalesce(PaperReviewPreference.preference, 0) as preference,
	coalesce(PaperReview.reviewType, 0) as reviewType,
	coalesce(PaperReview.reviewSubmitted, 0) as reviewSubmitted,
	coalesce($score, 0) as reviewScore,
	topicInterestScore,
	coalesce(PRR.contactId, 0) as refused
	from Paper join PCMember
	left join PaperConflict on (Paper.paperId=PaperConflict.paperId and PCMember.contactId=PaperConflict.contactId)
	left join PaperReviewPreference on (Paper.paperId=PaperReviewPreference.paperId and PCMember.contactId=PaperReviewPreference.contactId)
	left join PaperReview on (Paper.paperId=PaperReview.paperId and PCMember.contactId=PaperReview.contactId)
	left join (select paperId, PCMember.contactId,
		sum(if(interest=2,2,interest-1)) as topicInterestScore
		from PaperTopic join PCMember
		join TopicInterest on (TopicInterest.topicId=PaperTopic.topicId)
		group by paperId, PCMember.contactId) as PaperTopics on (Paper.paperId=PaperTopics.paperId and PCMember.contactId=PaperTopics.contactId)
	left join PaperReviewRefused PRR on (Paper.paperId=PRR.paperId and PCMember.contactId=PRR.contactId)
	group by Paper.paperId, PCMember.contactId");

    if ($atype == "rev" || $atype == "revadd" || $atype == "revpc") {
	while (($row = edb_orow($result))) {
	    $assignprefs["$row->paperId:$row->contactId"] = $row->preference;
	    if ($row->conflictType > 0 || $row->reviewType > 0
		|| $row->refused > 0)
		$prefs[$row->contactId][$row->paperId] = -1000001;
	    else
		$prefs[$row->contactId][$row->paperId] = max($row->preference, -1000) + ($row->topicInterestScore / 100);
	}
    } else {
	$scoredir = (substr(defval($_REQUEST, "${atype}score", "x"), 0, 1) == "-" ? -1 : 1);
	// First, collect score extremes
	$scoreextreme = array();
	$rows = array();
	while (($row = edb_orow($result))) {
	    $assignprefs["$row->paperId:$row->contactId"] = $row->preference;
	    if ($row->conflictType > 0 || $row->reviewType == 0
		|| $row->reviewSubmitted == 0 || $row->reviewScore == 0)
		/* ignore row */;
	    else {
		if (!isset($scoreextreme[$row->paperId])
		    || $scoredir * $row->reviewScore > $scoredir * $scoreextreme[$row->paperId])
		    $scoreextreme[$row->paperId] = $row->reviewScore;
		$rows[] = $row;
	    }
	}
	// Then, collect preferences; ignore score differences farther
	// than 1 score away from the relevant extreme
	foreach ($rows as $row) {
	    $scoredifference = $scoredir * ($row->reviewScore - $scoreextreme[$row->paperId]);
	    if ($scoredifference >= -1)
		$prefs[$row->contactId][$row->paperId] = max($scoredifference * 1001 + max(min($row->preference, 1000), -1000) + ($row->topicInterestScore / 100), -1000000);
	}
	$badpairs = array();	// bad pairs only relevant for reviews,
				// not discussion leads or shephers
	unset($rows);		// don't need the memory any more
    }

    // sort preferences
    foreach ($pcm as $pc) {
	arsort($prefs[$pc->contactId]);
	reset($prefs[$pc->contactId]);
    }

    // get papers
    $papers = array();
    $loadlimit = null;
    if ($atype == "revadd")
	$papers = array_fill_keys($papersel, rcvtint($_REQUEST["revaddct"]));
    else if ($atype == "revpc") {
	$loadlimit = rcvtint($_REQUEST["revpcct"]);
	$papers = array_fill_keys($papersel, ceil((count($pcm) * $loadlimit) / count($papersel)));
    } else if ($atype == "rev") {
	$papers = array_fill_keys($papersel, rcvtint($_REQUEST["revct"]));
	$result = $Conf->qe("select paperId, count(reviewId) from PaperReview where reviewType=$reviewtype group by paperId", "while counting reviews");
	while (($row = edb_row($result)))
	    if (isset($papers[$row[0]]))
		$papers[$row[0]] -= $row[1];
    } else if ($atype == "lead" || $atype == "shepherd") {
	$papers = array();
	$xpapers = array_fill_keys($papersel, 1);
	$result = $Conf->qe("select paperId from Paper where ${atype}ContactId=0", "while selecting reviews");
	while (($row = edb_row($result)))
	    if (isset($xpapers[$row[0]]))
		$papers[$row[0]] = 1;
    } else
	$papers = array_fill_keys($papersel, 1);

    // now, loop forever
    $pcids = array_keys($pcm);
    $progress = false;
    while (count($pcm)) {
	// choose a pc member at random, equalizing load
	$pc = null;
	foreach ($pcm as $pcx)
	    if ($pc == null || $load[$pcx->contactId] < $load[$pc]) {
		$numminpc = 0;
		$pc = $pcx->contactId;
	    } else if ($load[$pcx->contactId] == $load[$pc]) {
		$numminpc++;
		if (mt_rand(0, $numminpc) == 0)
		    $pc = $pcx->contactId;
	    }

	// traverse preferences in descending order until encountering an
	// assignable paper
	while (($pid = key($prefs[$pc])) !== null) {
	    $pref = current($prefs[$pc]);
	    next($prefs[$pc]);
	    if ($pref >= -1000000 && isset($papers[$pid]) && $papers[$pid] > 0
		&& (!isset($badpairs[$pc]) || noBadPair($pc, $pid, $prefs))) {
		// make assignment
		if (!isset($assignments[$pid]))
		    $assignments[$pid] = array();
		$assignments[$pid][] = $pc;
		$prefs[$pc][$pid] = -1000001;
		$papers[$pid]--;
		$load[$pc]++;
		break;
	    }
	}

	// if have exhausted preferences, remove pc member
	if ($pid === null || $load[$pc] === $loadlimit)
	    unset($pcm[$pc]);
    }

    // check for unmade assignments
    ksort($papers);
    $badpids = array();
    foreach ($papers as $pid => $n)
	if ($n > 0)
	    $badpids[] = $pid;
    if ($badpids && $atype != "revpc") {
	$b = array();
	$pidx = join("+", $badpids);
	foreach ($badpids as $pid)
	    $b[] = "<a href='" . hoturl("assign", "p=$pid&amp;list=$pidx") . "'>$pid</a>";
	$x = ($atype == "rev" || $atype == "revadd" ? ", possibly because of conflicts or previously declined reviews in the PC members you selected" : "");
	$y = (count($b) > 1 ? " (<a class='nowrap' href='" . hoturl("search", "q=$pidx") . "'>list them</a>)" : "");
	$Conf->warnMsg("I wasn’t able to complete the assignment$x.  The following papers got fewer than the required number of assignments: " . join(", ", $b) . $y . ".");
    }
    if (count($assignments) == 0) {
	$Conf->warnMsg("Nothing to assign.");
	unset($assignments);
    }
}

function saveAssign() {
    global $Conf, $Me;

    // check request
    if (!checkRequest($atype, $reviewtype, true))
	return false;

    // set round tag
    if ($_REQUEST["rev_roundtag"]) {
	$Conf->settings["rev_roundtag"] = 1;
	$Conf->settingTexts["rev_roundtag"] = $_REQUEST["rev_roundtag"];
    } else
	unset($Conf->settings["rev_roundtag"]);

    $Conf->qe("lock tables ContactInfo read, PCMember read, ChairAssistant read, Chair read, PaperReview write, PaperReviewRefused write, Paper write, PaperConflict write, ActionLog write" . $Conf->tagRoundLocker(($atype == "rev" || $atype == "revadd" || $atype == "revpc") && ($reviewtype == REVIEW_PRIMARY || $reviewtype == REVIEW_SECONDARY || $reviewtype == REVIEW_PC)));

    // parse assignment
    $pcm = pcMembers();
    $ass = array();
    foreach (explode(" ", $_REQUEST["ass"]) as $req) {
	$a = explode(",", $req);
	if (count($a) == 0 || ($pid = cvtint($a[0])) <= 0)
	    continue;
	$ass[$pid] = array();
	for ($i = 1; $i < count($a); $i++)
	    if (($pc = cvtint($a[$i])) > 0 && isset($pcm[$pc]))
		$ass[$pid][$pc] = true;
    }

    // magnanimous
    $didLead = false;
    $when = time();
    if ($atype == "rev" || $atype == "revadd" || $atype == "revpc") {
	$result = $Conf->qe("select PCMember.contactId, paperId,
		reviewId, reviewType, reviewModified
		from PCMember join PaperReview using (contactId)",
			"while getting existing reviews");
	while (($row = edb_orow($result)))
	    if (isset($ass[$row->paperId][$row->contactId])) {
		$Me->assignPaper($row->paperId, $row, $pcm[$row->contactId], $reviewtype, $when);
		unset($ass[$row->paperId][$row->contactId]);
	    }
	foreach ($ass as $pid => $pcs) {
	    foreach ($pcs as $pc => $ignore)
		$Me->assignPaper($pid, null, $pcm[$pc], $reviewtype, $when);
	}
    } else if ($atype == "prefconflict") {
	$q = "";
	foreach ($ass as $pid => $pcs) {
	    foreach ($pcs as $pc => $ignore)
		$q .= ", ($pid, $pc, " . CONFLICT_CHAIRMARK . ")";
	}
	$q = "insert into PaperConflict (paperId, contactId, conflictType) values "
	    . substr($q, 2)
	    . " on duplicate key update conflictType=greatest(conflictType," . CONFLICT_CHAIRMARK . ")";
	$Conf->qe($q, "while storing conflicts");
	$Conf->log("stored conflicts based on preferences", $Me);
    } else if ($atype == "clear") {
	if ($reviewtype == REVIEW_PRIMARY || $reviewtype == REVIEW_SECONDARY
	    || $reviewtype == REVIEW_PC) {
	    $result = $Conf->qe("select PCMember.contactId, paperId,
		reviewId, reviewType, reviewModified
		from PCMember join PaperReview using (contactId)
		where reviewType=$reviewtype",
			"while getting existing reviews");
	    while (($row = edb_orow($result)))
		if (isset($ass[$row->paperId][$row->contactId])) {
		    $Me->assignPaper($row->paperId, $row, $pcm[$row->contactId], 0, $when);
		    unset($ass[$row->paperId][$row->contactId]);
		}
	} else if ($reviewtype === "conflict") {
	    foreach ($ass as $pid => $pcs) {
		foreach ($pcs as $pc => $ignore)
		    $Conf->qe("delete from PaperConflict where paperId=$pid and contactId=$pc and conflictType<" . CONFLICT_AUTHOR, "while clearing conflicts");
	    }
	} else if ($reviewtype === "lead" || $reviewtype === "shepherd") {
	    foreach ($ass as $pid => $pcs) {
		foreach ($pcs as $pc => $ignore)
		    $Conf->qe("update Paper set ${reviewtype}ContactId=0 where paperId=$pid and ${reviewtype}ContactId=$pc", "while clearing ${reviewtype}s");
	    }
	}
    } else {
	foreach ($ass as $pid => $pcs)
	    if (count($pcs) == 1) {
		$Conf->qe("update Paper set ${atype}ContactId=" . key($pcs) . " where paperId=$pid", "while updating $atype");
		$didLead = true;
		$Conf->log("set $atype to " . $pcm[key($pcs)]->email, $Me, $pid);
	    }
    }

    // Confirmation message
    if ($Conf->sversion >= 46 && $Conf->setting("pcrev_assigntime") == $when)
	$Conf->confirmMsg("Assignments saved! You may want to <a href=\"" . hoturl("mail", "template=newpcrev") . "\">send mail about the new assignments</a>.");
    else
	$Conf->confirmMsg("Assignments saved!");

    // clean up
    $Conf->qe("unlock tables");
    $Conf->updateRevTokensSetting(false);

    if ($didLead && !$Conf->setting("paperlead")) {
	$Conf->qe("insert into Settings (name, value) values ('paperlead', 1) on duplicate key update value=1");
	$Conf->updateSettings();
    }
}

if (isset($_REQUEST["assign"]) && isset($_REQUEST["a"])
    && isset($_REQUEST["pctyp"]) && check_post())
    doAssign();
else if (isset($_REQUEST["saveassign"]) && isset($_REQUEST["a"])
	 && isset($_REQUEST["ass"]) && check_post())
    saveAssign();


$abar = "<div class='vbar'><table class='vbar'><tr><td><table><tr>\n";
$abar .= actionTab("Automatic", hoturl("autoassign"), true);
$abar .= actionTab("Manual", hoturl("manualassign"), false);
$abar .= actionTab("Upload", hoturl("bulkassign"), false);
$abar .= "</tr></table></td>\n<td class='spanner'></td>\n<td class='gopaper nowrap'>" . goPaperForm() . "</td></tr></table></div>\n";


$Conf->header("Review Assignments", "autoassign", $abar);


function doRadio($name, $value, $text, $extra = null) {
    if (($checked = (!isset($_REQUEST[$name]) || $_REQUEST[$name] == $value)))
	$_REQUEST[$name] = $value;
    $extra = ($extra ? $extra : array());
    $extra["id"] = "${name}_$value";
    echo tagg_radio($name, $value, $checked, $extra), "&nbsp;";
    if ($text != "")
	echo tagg_label($text, "${name}_$value");
}

function doSelect($name, $opts, $extra = null) {
    if (!isset($_REQUEST[$name]))
	$_REQUEST[$name] = key($opts);
    echo tagg_select($name, $opts, $_REQUEST[$name], $extra);
}

function divClass($name) {
    global $Error;
    return "<div" . (isset($Error[$name]) ? " class='error'" : "") . ">";
}


// Help list
$helplist = "<div class='helpside'><div class='helpinside'>
Assignment methods:
<ul><li><a href='" . hoturl("autoassign") . "' class='q'><strong>Automatic</strong></a></li>
 <li><a href='" . hoturl("manualassign") . "'>Manual by PC member</a></li>
 <li><a href='" . hoturl("assign") . "'>Manual by paper</a></li>
 <li><a href='" . hoturl("bulkassign") . "'>Upload</a></li>
</ul>
<hr class='hr' />
Types of PC review:
<dl><dt><img class='ass" . REVIEW_PRIMARY . "' src='images/_.gif' alt='Primary' /> Primary</dt><dd>Mandatory, may not be delegated</dd>
  <dt><img class='ass" . REVIEW_SECONDARY . "' src='images/_.gif' alt='Secondary' /> Secondary</dt><dd>Mandatory, may be delegated to external reviewers</dd>
  <dt><img class='ass" . REVIEW_PC . "' src='images/_.gif' alt='PC' /> Optional</dt><dd>May be declined</dd></dl>
</div></div>\n";


$extraclass = " initial";

if (isset($assignments) && count($assignments) > 0) {
    echo divClass("propass"), "<h3>Proposed assignment</h3>";
    $helplist = "";
    $Conf->infoMsg("If this assignment looks OK to you, select &ldquo;Save assignment&rdquo; to apply it.  (You can always alter the assignment afterwards.)  Reviewer preferences, if any, are shown as &ldquo;P#&rdquo;.");
    $extraclass = "";

    $atype = $_REQUEST["a"];
    if ($atype == "clear" || $atype == "rev" || $atype == "revadd" || $atype == "revpc")
	$reviewtype = $_REQUEST["${atype}type"];
    else
	$reviewtype = 0;
    if ($reviewtype == REVIEW_PRIMARY || $reviewtype == REVIEW_SECONDARY || $reviewtype == REVIEW_PC)
	$reviewtypename = strtolower($reviewTypeName[$reviewtype]) . " assignment";
    else if ($reviewtype === "conflict" || $atype == "prefconflict")
	$reviewtypename = "conflict assignment";
    else if ($reviewtype === "lead" || $atype == "lead")
	$reviewtypename = "discussion lead";
    else if ($reviewtype === "shepherd" || $atype == "shepherd")
	$reviewtypename = "shepherd";
    else
	$reviewtypename = "";

    ksort($assignments);
    $atext = array();
    $pcm = pcMembers();
    $nrev = countReviews();
    $conflictedPapers = conflictedPapers();
    $pc_nass = array();
    foreach ($assignments as $pid => $pcs) {
	$t = "";
	foreach ($pcm as $pc)
	    if (in_array($pc->contactId, $pcs)) {
		$t .= ($t ? ", " : "") . Text::name_html($pc);
		$pref = $assignprefs["$pid:$pc->contactId"];
		if ($pref !== "X" && $pref != 0)
		    $t .= " <span class='asspref" . ($pref > 0 ? 1 : -1)
			. "'>P" . decorateNumber($pref) . "</span>";
		$pc_nass[$pc->contactId] = defval($pc_nass, $pc->contactId, 0) + 1;
	    }
	if ($atype == "clear")
	    $t = "remove $t";
	if (isset($conflictedPapers[$pid])) {
            if ($conflictedPapers[$pid])
                $t = "<em>Hidden for conflict</em>";
            else
                $t = PaperList::wrapChairConflict($t);
        }
	$atext[$pid] = "<h6>Proposed $reviewtypename:</h6> $t";
    }

    $search = new PaperSearch($Me, array("t" => $_REQUEST["t"], "q" => join(" ", array_keys($assignments))));
    $plist = new PaperList($search, array("extraText" => $atext));
    $plist->showHeader = PaperList::HEADER_TITLES;
    $plist->display .= " reviewers ";
    echo $plist->text("reviewers", $Me);

    if ($atype != "prefconflict") {
	echo "<div class='g'></div>";
	echo "<strong>Assignment Summary</strong><br />\n";
	echo "<table class='pctb'><tr><td class='pctbcolleft'><table>";
	$colorizer = new Tagger;
	$pcdesc = array();
	foreach ($pcm as $id => $p) {
	    $nnew = defval($pc_nass, $id, 0);
	    if ($atype == "clear")
		$nnew = -$nnew;
	    if ($reviewtype >= REVIEW_PC && $reviewtype <= REVIEW_PRIMARY) {
		$nrev->any[$id] += $nnew;
		$nrev->pset->any[$id] += $nnew;
	    }
	    if ($reviewtype == REVIEW_PRIMARY) {
		$nrev->pri[$id] += $nnew;
		$nrev->pset->pri[$id] += $nnew;
	    }
	    if ($reviewtype == REVIEW_SECONDARY) {
		$nrev->sec[$id] += $nnew;
		$nrev->pset->sec[$id] += $nnew;
	    }
	    $color = $colorizer->color_classes($p->contactTags);
	    $color = ($color ? " class='${color}'" : "");
	    $c = "<tr$color><td class='pctbname pctbl'>"
		. Text::name_html($p)
		. ": " . plural($nnew, "assignment")
		. "</td></tr><tr$color><td class='pctbnrev pctbl'>"
		. review_count_report($nrev, $p, "After assignment:&nbsp;");
	    $pcdesc[] = $c . "</td></tr>\n";
	}
	$n = intval((count($pcdesc) + 2) / 3);
	for ($i = 0; $i < count($pcdesc); $i++) {
	    if (($i % $n) == 0 && $i)
		echo "</table></td><td class='pctbcolmid'><table>";
	    echo $pcdesc[$i];
	}
	echo "</table></td></tr></table>\n";
	$rev_roundtag = defval($_REQUEST, "rev_roundtag");
	if ($rev_roundtag == "(None)")
	    $rev_roundtag = "";
	if ($rev_roundtag)
	    echo "<strong>Review round:</strong> ", htmlspecialchars($rev_roundtag);
    }

    echo "<div class='g'></div>",
	"<form method='post' action='", hoturl_post("autoassign"), "' accept-charset='UTF-8'><div class='aahc'><div class='aa'>\n",
	"<input type='submit' class='b' name='saveassign' value='Save assignment' />\n",
	"&nbsp;<input type='submit' class='b' name='cancel' value='Cancel' />\n";
    foreach (array("t", "q", "a", "revtype", "revaddtype", "revpctype", "cleartype", "revct", "revaddct", "revpcct", "pctyp", "balance", "badpairs", "bpcount", "rev_roundtag") as $t)
	if (isset($_REQUEST[$t]))
	    echo "<input type='hidden' name='$t' value=\"", htmlspecialchars($_REQUEST[$t]), "\" />\n";
    echo "<input type='hidden' name='pcs' value='", join(" ", array_keys($pcsel)), "' />\n";
    for ($i = 1; $i <= 20; $i++) {
	if (defval($_REQUEST, "bpa$i"))
	    echo "<input type='hidden' name='bpa$i' value=\"", htmlspecialchars($_REQUEST["bpa$i"]), "\" />\n";
	if (defval($_REQUEST, "bpb$i"))
	    echo "<input type='hidden' name='bpb$i' value=\"", htmlspecialchars($_REQUEST["bpb$i"]), "\" />\n";
    }
    echo "<input type='hidden' name='p' value=\"", join(" ", $papersel), "\" />\n";

    // save the assignment
    echo "<input type='hidden' name='ass' value=\"";
    foreach ($assignments as $pid => $pcs)
	echo $pid, ",", join(",", $pcs), " ";
    echo "\" />\n";

    echo "</div></div></form></div>\n";
    $Conf->footer();
    exit;
}

echo "<form method='post' action='", hoturl_post("autoassign"), "' accept-charset='UTF-8'><div class='aahc'>", $helplist,
    "<input id='defaultact' type='hidden' name='defaultact' value='' />",
    "<input class='hidden' type='submit' name='default' value='1' />";

// paper selection
echo divClass("pap"), "<h3>Paper selection</h3>";
if (!isset($_REQUEST["q"]))
    $_REQUEST["q"] = join(" ", $papersel);
if ($Conf->has_managed_submissions())
    $tOpt = array("unm" => "Unmanaged submissions",
                  "s" => "All submissions");
else
    $tOpt = array("s" => "Submitted papers");
$tOpt["acc"] = "Accepted papers";
$tOpt["und"] = "Undecided papers";
$tOpt["all"] = "All papers";
if (!isset($_REQUEST["t"]) || !isset($tOpt[$_REQUEST["t"]]))
    $_REQUEST["t"] = "s";
$q = ($_REQUEST["q"] == "" ? "(All)" : $_REQUEST["q"]);
echo "<input id='autoassignq' class='textlite temptextoff' type='text' size='40' name='q' value=\"", htmlspecialchars($q), "\" onfocus=\"autosub('requery',this)\" onchange='highlightUpdate(\"requery\")' title='Enter paper numbers or search terms' /> &nbsp;in &nbsp;",
    tagg_select("t", $tOpt, $_REQUEST["t"], array("onchange" => "highlightUpdate(\"requery\")")),
    " &nbsp; <input id='requery' class='b' name='requery' type='submit' value='List' />\n";
$Conf->footerScript("mktemptext('autoassignq','(All)')");
if (isset($_REQUEST["requery"]) || isset($_REQUEST["prevpap"])) {
    echo "<br /><span class='hint'>Assignments will apply to the selected papers.</span>
<div class='g'></div>";

    $search = new PaperSearch($Me, array("t" => $_REQUEST["t"], "q" => $_REQUEST["q"]));
    $plist = new PaperList($search);
    $plist->showHeader = PaperList::HEADER_TITLES;
    $plist->display .= " reviewers ";
    $plist->footer = false;
    $plist->papersel = array_fill_keys($papersel, 1);
    foreach (preg_split('/\s+/', defval($_REQUEST, "prevpap")) as $p)
	if (!isset($plist->papersel[$p]))
	    $plist->papersel[$p] = 0;
    echo $plist->text("reviewersSel", $Me);
    echo "<input type='hidden' name='prevt' value=\"", htmlspecialchars($_REQUEST["t"]), "\" />",
	"<input type='hidden' name='prevq' value=\"", htmlspecialchars($_REQUEST["q"]), "\" />";
    if ($plist->ids)
	echo "<input type='hidden' name='prevpap' value=\"", htmlspecialchars(join(" ", $plist->ids)), "\" />";
}
echo "</div>\n";
// echo "<tr><td class='caption'></td><td class='entry'><div class='g'></div></td></tr>\n";


// action
echo divClass("ass"), "<h3>Action</h3>", divClass("rev");
doRadio("a", "rev", "Ensure each paper has <i>at least</i>");
echo "&nbsp; <input type='text' class='textlite' name='revct' value=\"", htmlspecialchars(defval($_REQUEST, "revct", 1)), "\" size='3' onfocus='autosub(\"assign\",this)' />&nbsp; ";
doSelect("revtype", array(REVIEW_PRIMARY => "primary", REVIEW_SECONDARY => "secondary", REVIEW_PC => "optional"));
echo "&nbsp; review(s)</div>\n";

echo divClass("revadd");
doRadio("a", "revadd", "Assign");
echo "&nbsp; <input type='text' class='textlite' name='revaddct' value=\"", htmlspecialchars(defval($_REQUEST, "revaddct", 1)), "\" size='3' onfocus='autosub(\"assign\",this)' />&nbsp; ",
    "<i>additional</i>&nbsp; ";
doSelect("revaddtype", array(REVIEW_PRIMARY => "primary", REVIEW_SECONDARY => "secondary", REVIEW_PC => "optional"));
echo "&nbsp; review(s) per paper</div>\n";

echo divClass("revpc");
doRadio("a", "revpc", "Assign each PC member");
echo "&nbsp; <input type='text' class='textlite' name='revpcct' value=\"", htmlspecialchars(defval($_REQUEST, "revpcct", 1)), "\" size='3' onfocus='autosub(\"assign\",this)' />&nbsp; additional&nbsp; ";
doSelect("revpctype", array(REVIEW_PRIMARY => "primary", REVIEW_SECONDARY => "secondary", REVIEW_PC => "optional"));
echo "&nbsp; review(s) from this paper selection</div>\n";

// Review round
echo divClass("rev_roundtag");
echo "<input style='visibility: hidden' type='radio' class='cb' name='a' value='rev_roundtag' disabled='disabled' />&nbsp;";
echo "Review round: &nbsp;";
$rev_roundtag = defval($_REQUEST, "rev_roundtag", $Conf->settingText("rev_roundtag"));
if (!$rev_roundtag)
    $rev_roundtag = "(None)";
echo "<input id='rev_roundtag' class='textlite temptextoff' type='text' size='15' name='rev_roundtag' value=\"",
    htmlspecialchars($rev_roundtag),
    "\" onfocus=\"autosub('assign',this)\" />",
    " &nbsp;<a class='hint' href='", hoturl("help", "t=revround"), "'>What is this?</a></div>
<div class='g'></div>\n";
$Conf->footerScript("mktemptext('rev_roundtag','(None)')");

doRadio('a', 'prefconflict', 'Assign conflicts when PC members have review preferences of &minus;100 or less');
echo "<br />\n";

doRadio('a', 'lead', 'Assign discussion lead from reviewers, preferring&nbsp; ');
doSelect('leadscore', $scoreselector);
echo "<br />\n";

doRadio('a', 'shepherd', 'Assign shepherd from reviewers, preferring&nbsp; ');
doSelect('shepherdscore', $scoreselector);

echo "<div class='g'></div>", divClass("clear");
doRadio('a', 'clear', 'Clear all &nbsp;');
doSelect('cleartype', array(REVIEW_PRIMARY => "primary", REVIEW_SECONDARY => "secondary", REVIEW_PC => "optional", "conflict" => "conflict", "lead" => "discussion lead", "shepherd" => "shepherd"));
echo " &nbsp;assignments for selected papers and PC members";
echo "</div></div>\n";


// PC
//echo "<tr><td class='caption'></td><td class='entry'><div class='g'></div></td></tr>\n";

echo "<h3>PC members</h3><table><tr><td>";
doRadio("pctyp", "all", "");
echo "</td><td>", tagg_label("Use entire PC", "pctyp_all"), "</td></tr>\n";

echo "<tr><td>";
doRadio('pctyp', 'sel', '');
echo "</td><td>", tagg_label("Use selected PC members:", "pctyp_sel"), " &nbsp; (Select ";
$pctyp_sel = array(array("all", 1, "All"), array("none", 0, "None"));
$pctags = pcTags();
if (count($pctags)) {
    $tagsjson = array();
    foreach (pcMembers() as $pc)
	if ($pc->contactTags)
	    $tagsjson[] = "\"$pc->contactId\":\"" . strtolower($pc->contactTags) . "\"";
    $Conf->footerScript("pc_tags_json={" . join(",", $tagsjson) . "};");
    foreach ($pctags as $tagname => $pctag)
	$pctyp_sel[] = array($pctag, "pc_tags_members(\"$tagname\")", "“${pctag}”&nbsp;tag");
}
$sep = "";
foreach ($pctyp_sel as $pctyp) {
    echo $sep, "<a href='#pc_", $pctyp[0], "' onclick='",
        "papersel(", $pctyp[1], ",\"pcs[]\");\$\$(\"pctyp_sel\").checked=true;return false'>",
        $pctyp[2], "</a>";
    $sep = ", ";
}
echo ")</td></tr>\n<tr><td></td><td><table class='pctb'><tr><td class='pctbcolleft'><table>";

$pcm = pcMembers();
$nrev = countReviews();
$pcdesc = array();
$colorizer = new Tagger;
foreach ($pcm as $id => $p) {
    $count = count($pcdesc) + 1;
    $color = $colorizer->color_classes($p->contactTags);
    $color = ($color ? " class='${color}'" : "");
    $c = "<tr$color><td class='pctbl'>"
	. tagg_checkbox("pcs[]", $id, isset($pcsel[$id]),
			array("id" => "pcsel$count",
			      "onclick" => "pselClick(event,this);\$\$('pctyp_sel').checked=true"))
	. "&nbsp;</td><td class='pctbname'>"
	. tagg_label(Text::name_html($p), "pcsel$count")
	. "</td></tr><tr$color><td class='pctbl'></td><td class='pctbnrev'>"
	. review_count_report($nrev, $p, "")
	. "</td></tr>";
    $pcdesc[] = $c;
}
$n = intval((count($pcdesc) + 2) / 3);
for ($i = 0; $i < count($pcdesc); $i++) {
    if (($i % $n) == 0 && $i)
	echo "</table></td><td class='pctbcolmid'><table>";
    echo $pcdesc[$i];
}
echo "</table></td></tr></table></td></tr></table>";


// Bad pairs
$numBadPairs = 1;
$badPairSelector = null;

function bpSelector($i, $which) {
    global $numBadPairs, $badPairSelector, $pcm;
    if (!$badPairSelector) {
	$badPairSelector = array("0" => "(PC member)");
	foreach ($pcm as $pc)
	    $badPairSelector[$pc->contactId] = Text::name_html($pc);
    }
    $selected = ($i <= $_REQUEST["bpcount"] ? defval($_REQUEST, "bp$which$i") : "0");
    if ($selected && isset($badPairSelector[$selected]))
	$numBadPairs = max($i, $numBadPairs);
    return tagg_select("bp$which$i", $badPairSelector, $selected,
		       array("onchange" => "if(!((x=\$\$(\"badpairs\")).checked)) x.click()"));
}

echo "<div class='g'></div><div class='relative'><table id='bptable'>\n";
for ($i = 1; $i <= 50; $i++) {
    $selector_text = bpSelector($i, "a") . " &nbsp;and&nbsp; " . bpSelector($i, "b");
    echo "    <tr id='bp$i' class='", ($numBadPairs >= $i ? "auedito" : "aueditc"),
	"'><td class='rentry nowrap'>";
    if ($i == 1)
	echo tagg_checkbox("badpairs", 1, isset($_REQUEST["badpairs"]),
			   array("id" => "badpairs")),
	    "&nbsp;", tagg_label("Don’t assign", "badpairs"), " &nbsp;";
    else
	echo "or &nbsp;";
    echo "</td><td class='lentry'>", $selector_text;
    if ($i == 1)
	echo " &nbsp;to the same paper &nbsp;(<a href='javascript:void authorfold(\"bp\",1,1)'>More</a> | <a href='javascript:void authorfold(\"bp\",1,-1)'>Fewer</a>)";
    echo "</td></tr>\n";
}
echo "</table><input id='bpcount' type='hidden' name='bpcount' value='50' />";
$Conf->echoScript("authorfold(\"bp\",0,$numBadPairs)");
echo "</div>\n";


// Load balancing
// echo "<tr><td class='caption'></td><td class='entry'><div class='g'></div></td></tr>\n";
echo "<h3>Load balancing</h3>";
doRadio('balance', 'new', "Spread new assignments equally among PC members");
echo "<br />";
doRadio('balance', 'all', "Spread assignments so that PC members have roughly equal overall load");


// Create assignment
echo "<div class='g'></div>\n";
echo "<div class='aa'><input type='submit' class='b' name='assign' value='Prepare assignment' /> &nbsp; <span class='hint'>You’ll be able to check the assignment before it is saved.</span></div>\n";


echo "</div></form>";

$Conf->footer();
