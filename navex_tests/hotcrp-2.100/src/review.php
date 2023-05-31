<?php
// review.php -- HotCRP helper class for producing review forms and tables
// HotCRP is Copyright (c) 2006-2016 Eddie Kohler and Regents of the UC
// Distributed under an MIT-like license; see LICENSE

// JSON schema for settings["review_form"]:
// {FIELD:{"name":NAME,"description":DESCRIPTION,"position":POSITION,
//         "display_space":ROWS,"visibility":VISIBILITY,
//         "options":[DESCRIPTION,...],"option_letter":LEVELCHAR}}

class ReviewField {
    const VALUE_NONE = 0;
    const VALUE_SC = 1;
    const VALUE_REV_NUM = 2;

    public $id;
    public $uid;
    public $name;
    public $name_html;
    public $description;
    public $abbreviation;
    public $has_options;
    public $options = array();
    public $option_letter = false;
    public $display_space;
    public $view_score;
    public $displayed = false;
    public $display_order;
    public $option_class_prefix = "sv";
    public $round_mask = 0;
    public $allow_empty = false;
    private $analyzed = false;

    static private $view_score_map = [
        "secret" => VIEWSCORE_ADMINONLY, "admin" => VIEWSCORE_REVIEWERONLY,
        "pc" => VIEWSCORE_PC,
        "audec" => VIEWSCORE_AUTHORDEC, "authordec" => VIEWSCORE_AUTHORDEC,
        "au" => VIEWSCORE_AUTHOR, "author" => VIEWSCORE_AUTHOR
    ];
    // Hard-code the database's `view_score` values as of January 2016
    static private $view_score_upgrade_map = [
        "-2" => "secret", "-1" => "admin", "0" => "pc", "1" => "au"
    ];
    static private $view_score_rmap = [
        VIEWSCORE_ADMINONLY => "secret", VIEWSCORE_REVIEWERONLY => "admin",
        VIEWSCORE_PC => "pc", VIEWSCORE_AUTHORDEC => "audec",
        VIEWSCORE_AUTHOR => "au"
    ];

    public function __construct($id, $has_options) {
        $this->id = $id;
        $this->has_options = $has_options;
    }

    public function assign($j) {
        $this->name = (get($j, "name") ? : "Field name");
        $this->name_html = htmlspecialchars($this->name);
        $this->description = (get($j, "description") ? : "");
        $this->display_space = get_i($j, "display_space");
        if (!$this->has_options && $this->display_space < 3)
            $this->display_space = 3;
        $vis = get($j, "visibility");
        if ($vis === null) {
            $vis = get($j, "view_score");
            if (is_int($vis))
                $vis = self::$view_score_upgrade_map[$vis];
        }
        $this->view_score = VIEWSCORE_PC;
        if (is_string($vis) && isset(self::$view_score_map[$vis]))
            $this->view_score = self::$view_score_map[$vis];
        if (get($j, "position")) {
            $this->displayed = true;
            $this->display_order = $j->position;
        } else
            $this->displayed = $this->display_order = false;
        $this->round_mask = get_i($j, "round_mask");
        if ($this->has_options) {
            $options = get($j, "options") ? : array();
            $ol = get($j, "option_letter");
            if ($ol && ctype_alpha($ol) && strlen($ol) == 1)
                $this->option_letter = ord($ol) + count($options);
            else if ($ol && (is_int($ol) || ctype_digit($ol)))
                $this->option_letter = (int) $ol;
            else
                $this->option_letter = false;
            $this->options = array();
            if ($this->option_letter) {
                foreach (array_reverse($options, true) as $i => $n)
                    $this->options[chr($this->option_letter - $i - 1)] = $n;
            } else {
                foreach ($options as $i => $n)
                    $this->options[$i + 1] = $n;
            }
            if (($p = get($j, "option_class_prefix")))
                $this->option_class_prefix = $p;
            if (get($j, "allow_empty"))
                $this->allow_empty = true;
        }
        $this->analyzed = false;
    }

    public function unparse_json() {
        global $Conf;
        $j = (object) array("name" => $this->name);
        if ($this->description)
            $j->description = $this->description;
        if (!$this->has_options && $this->display_space > 3)
            $j->display_space = $this->display_space;
        if ($this->displayed)
            $j->position = $this->display_order;
        $j->visibility = $this->unparse_visibility();
        if ($this->has_options) {
            $j->options = array();
            foreach ($this->options as $otext)
                $j->options[] = $otext;
            if ($this->option_letter) {
                $j->options = array_reverse($j->options);
                $j->option_letter = chr($this->option_letter - count($j->options));
            }
            if ($this->option_class_prefix !== "sv")
                $j->option_class_prefix = $this->option_class_prefix;
            if ($this->allow_empty)
                $j->allow_empty = true;
        }
        if ($this->round_mask) {
            $j->round_list = array();
            foreach ($Conf->round_list() as $i => $round_name)
                if ($this->round_mask & (1 << $i))
                    $j->round_list[] = $i ? $round_name : "unnamed";
        }
        return $j;
    }

    static public function unparse_visibility_value($vs) {
        if (isset(self::$view_score_rmap[$vs]))
            return self::$view_score_rmap[$vs];
        else
            return $vs;
    }

    public function unparse_visibility() {
        return self::unparse_visibility_value($this->view_score);
    }

    public function is_round_visible($rrow) {
        global $Conf;
        $round = $rrow ? $rrow->reviewRound : $Conf->current_round();
        return !$this->round_mask
            || $round === null
            || ($this->round_mask & (1 << $round))
            || ($rrow && ($fid = $this->id) && $rrow->$fid);
    }

    public function include_word_count() {
        return !$this->has_options && $this->view_score >= VIEWSCORE_AUTHORDEC;
    }

    public function analyze() {
        if ($this->has_options && !$this->analyzed) {
            $scores = array_keys($this->options);
            if (count($scores) == 1) {
                $this->typical_score = $scores[0];
                unset($this->typical_score_range);
            } else {
                $off = count($scores) == 2 ? 0 : 1;
                $this->typical_score0 = $scores[$off];
                $this->typical_score = $scores[$off + 1];
                if ($this->option_letter)
                    $this->typical_score_range = $this->typical_score0 . $this->typical_score;
                else
                    $this->typical_score_range = $this->typical_score0 . "-" . $this->typical_score;
            }
            $this->analyzed = true;
        }
        return $this;
    }

    public function web_abbreviation() {
        return '<span class="hottooltip" data-hottooltip="' . $this->name_html
            . '" data-hottooltip-dir="b">' . htmlspecialchars($this->abbreviation) . "</span>";
    }

    static function make_abbreviation($name, $abbrdetail, $abbrtype) {
        $name = str_replace("'", "", $name);

        // try to filter out noninteresting words
        if ($abbrdetail < 2) {
            $xname = preg_replace('/\b(?:a|an|be|did|do|for|in|of|question|the|this|to|with|you)\b/i', '', $name);
            $name = $xname ? : $name;
        }

        // only letters & digits
        if ($abbrdetail == 0)
            $name = preg_replace('/\(.*?\)/', ' ', $name);
        $xname = preg_replace('/[-:\s,.?!()\[\]\{\}_\/\'\"]+/', " ", " $name ");
        // drop extraneous words
        $xname = preg_replace('/\A(' . str_repeat(' \S+', max(3, $abbrdetail)) . ' ).*\z/', '$1', $xname);
        if ($abbrtype == 1)
            return strtolower(str_replace(" ", "-", trim($xname)));
        else {
            // drop lowercase letters from words
            $xname = str_replace(" ", "", ucwords($xname));
            return preg_replace('/([A-Z][a-z][a-z])[a-z]*/', '$1', $xname);
        }
    }

    public function abbreviation1() {
        return self::make_abbreviation($this->name, 0, 1);
    }

    public function value_class($value) {
        if (count($this->options) > 1)
            $n = (int) (($value - 1) * 8.0 / (count($this->options) - 1) + 1.5);
        else
            $n = 1;
        return "sv " . $this->option_class_prefix . $n;
    }

    public function unparse_value($value, $scclass = false) {
        if (is_object($value))
            $value = defval($value, $this->id);
        if (!$this->has_options)
            return $value;
        else if (!$value)
            return null;
        else if (!$this->option_letter)
            $x = (int) $value;
        else if (is_int($value) || ctype_digit($value))
            $x = chr($this->option_letter - (int) $value);
        else {
            $value = $this->option_letter - ord($value);
            $x = chr($this->option_letter - $value);
        }
        if (!$scclass)
            return $x;
        $vc = $this->value_class($value);
        $klass = $vc;
        if ($scclass & self::VALUE_REV_NUM) {
            $klass = "rev_num $klass";
            $x .= ".";
        }
        return "<span class=\"$klass\">$x</span>";
    }

    public function value_description($value) {
        if (is_object($value))
            $value = defval($value, $this->id);
        if (!$this->has_options)
            return null;
        else if (!$value)
            return "";
        else if ($this->option_letter && (is_int($value) || ctype_digit($value)))
            $value = chr($this->option_letter - (int) $value);
        return $this->options[$value];
    }

    static public function unparse_letter($option_letter, $value) {
        $ivalue = (int) $value;
        $ch = $option_letter - $ivalue;
        if ($value < $ivalue + 0.25)
            return chr($ch);
        else if ($value < $ivalue + 0.75)
            return chr($ch - 1) . chr($ch);
        else
            return chr($ch - 1);
    }

    public function unparse_average($value) {
        assert($this->has_options);
        if ($value <= 0.8)
            return "";
        else if (!$this->option_letter)
            return sprintf("%0.2f", $value);
        else
            return self::unparse_letter($this->option_letter, $value);
    }

    public function unparse_graph($v, $style, $myscore) {
        global $Conf;
        assert($this->has_options);
        $max = count($this->options);

        if (!is_object($v))
            $v = scoreCounts($v, $max);

        $avgtext = $this->unparse_average($v->avg);
        if ($v->n > 1 && $v->stddev)
            $avgtext .= sprintf(" &plusmn; %0.2f", $v->stddev);

        $args = "v=";
        for ($key = 1; $key <= $max; $key++)
            $args .= ($args == "v=" ? "" : ",") . $v->v[$key];
        if ($myscore && $v->v[$myscore] > 0)
            $args .= "&amp;h=$myscore";
        if ($this->option_letter)
            $args .= "&amp;c=" . chr($this->option_letter - 1);
        if ($this->option_class_prefix !== "sv")
            $args .= "&amp;sv=" . urlencode($this->option_class_prefix);

        if ($style == 1) {
            $width = 5 * $max + 3;
            $height = 5 * max(3, max($v->v)) + 3;
            $retstr = "<div class=\"need-scorechart\" style=\"width:${width}px;height:${height}px\" data-scorechart=\"$args&amp;s=1\" title=\"$avgtext\"></div>";
        } else if ($style == 2) {
            $retstr = "<div class=\"sc\">"
                . "<div class=\"need-scorechart\" style=\"width:64px;height:8px\" data-scorechart=\"$args&amp;s=2\" title=\"$avgtext\"></div>"
                . "<br />";
            if ($this->option_letter) {
                for ($key = $max; $key >= 1; $key--)
                    $retstr .= ($key < $max ? " " : "") . '<span class="' . $this->value_class($key) . '">' . $v->v[$key] . "</span>";
            } else {
                for ($key = 1; $key <= $max; $key++)
                    $retstr .= ($key > 1 ? " " : "") . '<span class="' . $this->value_class($key) . '">' . $v->v[$key] . "</span>";
            }
            $retstr .= '<br /><span class="sc_sum">' . $avgtext . "</span></div>";
        }
        $Conf->footerScript("$(scorechart)", "scorechart");

        return $retstr;
    }

    public function parse_is_empty($text) {
        return $text === "" || $text === "0" || $text[0] === "("
            || strcasecmp($text, "No entry") == 0;
    }

    public function parse_value($text, $strict) {
        if (!$strict && strlen($text) > 1
            && preg_match('/\A\s*([0-9]+|[A-Z])(\W|\z)/', $text, $m))
            $text = $m[1];
        if (!$strict && ctype_digit($text))
            $text = (int) $text;
        if (!$text || !$this->has_options || !isset($this->options[$text]))
            return null;
        else if ($this->option_letter)
            return $this->option_letter - ord($text);
        else
            return $text;
    }
}

class ReviewForm {
    public $fmap = array();
    public $forder;
    public $fieldName;
    private $mailer_info;
    private $mailer_preps;

    static public $revtype_names = ["None", "External", "PC", "Secondary", "Primary"];

    // XXX all negative ratings should have negative numbers
    // values are HTML
    static public $rating_types = array("n" => "average",
                                        1 => "very helpful",
                                        0 => "too short",
                                        -1 => "too vague",
                                        -4 => "too narrow",
                                        -2 => "not constructive",
                                        -3 => "not correct");
    static private $cache = null;
    static private $review_author_seen = null;

    static public function fmap_compare($a, $b) {
        if ($a->displayed != $b->displayed)
            return $a->displayed ? -1 : 1;
        else if ($a->displayed)
            return $a->display_order - $b->display_order;
        else
            return strcmp($a->id, $b->id);
    }

    public function __construct($rfj) {
        // prototype fields
        foreach (array("paperSummary", "commentsToAuthor", "commentsToPC",
                       "commentsToAddress", "weaknessOfPaper",
                       "strengthOfPaper", "textField7", "textField8") as $fid)
            $this->fmap[$fid] = new ReviewField($fid, false);
        foreach (array("potential", "fixability", "overAllMerit",
                       "reviewerQualification", "novelty", "technicalMerit",
                       "interestToCommunity", "longevity", "grammar",
                       "likelyPresentation", "suitableForShort") as $fid)
            $this->fmap[$fid] = new ReviewField($fid, true);

        // parse JSON
        if (!$rfj)
            $rfj = json_decode('{
"overAllMerit":{"name":"Overall merit","position":1,"visibility":"au",
  "options":["Reject","Weak reject","Weak accept","Accept","Strong accept"]},
"reviewerQualification":{"name":"Reviewer expertise","position":2,"visibility":"au",
  "options":["No familiarity","Some familiarity","Knowledgeable","Expert"]},
"paperSummary":{"name":"Paper summary","position":3,"display_space":5,"visibility":"au"},
"commentsToAuthor":{"name":"Comments to authors","position":4,"visibility":"au"},
"commentsToPC":{"name":"Comments to PC","position":5,"visibility":"pc"}}');

        foreach ($rfj as $fname => $j)
            if (($f = get($this->fmap, $fname)))
                $f->assign($j);

        // assign field order
        $forder = array();
        $this->fieldName = array();
        foreach ($this->fmap as $fid => $f) {
            $this->fieldName[strtolower($f->name)] = $fid;
            if ($f->displayed)
                $forder[sprintf("%03d.%s", $f->display_order, $fid)] = $f;
        }
        ksort($forder);
        $n = 0;
        foreach ($forder as $f)
            $f->display_order = ++$n;
        uasort($this->fmap, "ReviewForm::fmap_compare");
        $this->forder = array();
        foreach ($this->fmap as $f)
            if ($f->displayed)
                $this->forder[$f->id] = $f;

        // set field abbreviations; try to ensure uniqueness
        for ($detail = 0; $detail < 5; ++$detail) {
            $fmap = array();
            foreach ($this->forder as $f) {
                $f->abbreviation = ReviewField::make_abbreviation($f->name, $detail, 0);
                $fmap[$f->abbreviation] = true;
            }
            if (count($fmap) == count($this->forder))
                break;
        }
        foreach ($this->forder as $f)
            $f->uid = $detail < 5 ? $f->abbreviation : $f->id;
    }

    static public function get() {
        global $Conf;
        if (!self::$cache)
            self::$cache = new ReviewForm($Conf->review_form_json());
        return self::$cache;
    }

    static public function clear_cache() {
        self::$cache = null;
    }

    static public function all_fields() {
        $rf = self::get();
        return $rf->forder;
    }

    static public function field($fid) {
        $rf = self::get();
        $f = get($rf->fmap, $fid);
        return $f && $f->displayed ? $f : null;
    }

    static public function field_search($name) {
        $rf = self::get();
        $f = get($rf->fmap, $name);
        if ($f && $f->displayed)
            return $f;
        $a = preg_split("/([^a-zA-Z0-9])/", $name, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0; $i < count($a); ++$i)
            $a[$i] = preg_quote($a[$i], "/");
        $field = null;
        foreach ($rf->forder as $f) {
            if (count($a) == 1 && strcasecmp($a[0], $f->abbreviation) == 0)
                return $f;
            for ($i = 0; $i < count($a); ++$i)
                if ($a[$i] != "-" && $a[$i] != "\\-" && $a[$i] != "_"
                    && !preg_match("{\\b$a[$i]}i", $f->name))
                    break;
            if ($i == count($a))
                $field = ($field === null ? $f : false);
        }
        return $field;
    }

    public function unparse_full_json() {
        $fmap = array();
        foreach ($this->fmap as $f)
            $fmap[$f->id] = $f->unparse_json();
        return $fmap;
    }

    public function unparse_json($round_mask, $view_score_bound) {
        $fmap = array();
        foreach ($this->fmap as $f)
            if ($f->displayed
                && (!$round_mask || !$f->round_mask
                    || ($f->round_mask & $round_mask))
                && $f->view_score > $view_score_bound) {
                $fmap[$f->uid] = $f->unparse_json();
            }
        return $fmap;
    }

    public function unparse_ratings_json() {
        $rt = self::$rating_types;
        $rt["order"] = array_keys(self::$rating_types);
        return $rt;
    }

    static public function reviewArchiveFields() {
        global $Conf;
        $rf = self::get();
        $x = "reviewId, paperId, contactId, reviewType, requestedBy,
                reviewModified, reviewSubmitted, reviewNeedsSubmit, "
            . join(", ", array_keys($rf->fmap))
            . ", reviewRound, reviewNotified, timeRequested, timeRequestNotified,
            reviewToken, reviewAuthorNotified";
        if ($Conf->sversion >= 92)
            $x .= ", reviewAuthorSeen";
        if ($Conf->sversion >= 98)
            $x .= ", reviewWordCount";
        return $x;
    }

    private static function format_description($rrow, $text) {
        if (($f = Conf::format_info($rrow ? $rrow->reviewFormat : null))) {
            if ($text && ($t = get($f, "description_text")))
                return $t;
            $t = get($f, "description");
            if ($text && $t)
                $t = self::cleanDescription($t);
            return $t;
        }
        return null;
    }

    private function webFormRows($contact, $prow, $rrow, $useRequest = false) {
        global $ReviewFormError, $Conf;
        $format_description = self::format_description($rrow, false);
        $revViewScore = $contact->view_score_bound($prow, $rrow);
        echo '<div class="rve">';
        foreach ($this->forder as $field => $f) {
            if ($f->view_score <= $revViewScore
                || ($f->round_mask && !$f->is_round_visible($rrow)))
                continue;

            $fval = "";
            if ($useRequest && isset($_REQUEST[$field]))
                $fval = $_REQUEST[$field];
            else if ($rrow)
                $fval = $f->unparse_value($rrow->$field);

            echo '<div class="rv rveg" data-rf="', $f->uid, '"><div class="revet';
            if (isset($ReviewFormError[$field]))
                echo " error";
            echo '"><div class="revfn">', $f->name_html;
            if ($f->view_score < VIEWSCORE_REVIEWERONLY)
                echo '<div class="revvis">(secret)</div>';
            else if ($f->view_score < VIEWSCORE_PC)
                echo '<div class="revvis">(shown only to chairs)</div>';
            else if ($f->view_score < VIEWSCORE_AUTHOR)
                echo '<div class="revvis">(hidden from authors)</div>';
            echo '</div><hr class="c" /></div>';

            if ($f->description)
                echo '<div class="revhint">', $f->description, "</div>";

            echo '<div class="revev">';
            if ($f->has_options) {
                echo '<select name="', $field, '" onchange="hiliter(this)">';
                $noentry = $f->allow_empty ? "No entry" : "(Your choice here)";
                if (!$f->parse_value($fval, true))
                    echo '<option value="0" selected="selected">', $noentry, '</option>';
                else if ($f->allow_empty)
                    echo '<option value="0">', $noentry, '</option>';
                foreach ($f->options as $num => $what) {
                    echo '<option value="', $num, '"';
                    if ($num == $fval)
                        echo ' selected="selected"';
                    echo ">$num. ", htmlspecialchars($what), "</option>";
                }
                echo "</select>";
            } else {
                if ($format_description)
                    echo $format_description;
                echo Ht::textarea($field, $fval,
                        array("class" => "reviewtext need-autogrow", "rows" => $f->display_space,
                              "cols" => 60, "onchange" => "hiliter(this)",
                              "spellcheck" => "true"));
            }
            echo "</div></div>\n";
        }
        echo "</div>\n";
    }

    function tfError(&$tf, $isError, $text, $field = null) {
        $e = htmlspecialchars($tf['filename']) . ":";
        if (is_int($field))
            $e .= $field;
        else if ($field === null || !isset($tf['fieldLineno'][$field]))
            $e .= $tf['firstLineno'];
        else
            $e .= $tf['fieldLineno'][$field];
        if (defval($tf, 'paperId'))
            $e .= " (paper #" . $tf['paperId'] . ")";
        $tf[$isError ? 'anyErrors' : 'anyWarnings'] = true;
        $tf['err'][] = "<span class='lineno'>" . $e . ":</span> " . $text;
        return false;
    }

    function checkRequestFields(&$req, $rrow, &$tf = null) {
        global $ReviewFormError, $Conf;
        $submit = defval($req, "ready", false);
        unset($req["unready"]);
        $nokfields = 0;
        foreach ($this->forder as $field => $f) {
            if (!isset($req[$field]) && !$submit)
                continue;
            $fval = defval($req, $field, ($rrow ? $rrow->$field : ""));
            if (!isset($req[$field])) {
                if ($f->round_mask && !$f->is_round_visible($rrow))
                    continue;
                if ($f->view_score >= VIEWSCORE_PC)
                    $missing[] = $f->name;
            }
            if ($f->has_options) {
                $fval = trim($fval);
                if ($f->parse_is_empty($fval)) {
                    if ($submit && $f->view_score >= VIEWSCORE_PC
                        && !$f->allow_empty) {
                        $provide[] = $f->name;
                        $ReviewFormError[$field] = 1;
                    }
                } else if (!$f->parse_value($fval, false)) {
                    $outofrange[] = $f;
                    $ReviewFormError[$field] = 1;
                } else
                    $nokfields++;
            } else if (trim($fval) !== "")
                $nokfields++;
        }
        if (isset($missing) && $tf)
            self::tfError($tf, false, commajoin($missing) . " " . pluralx($missing, "field") . " missing from form.  Preserving any existing values.");
        if ($rrow && defval($rrow, "reviewEditVersion", 0) > defval($req, "version", 0)
            && $nokfields > 0 && $tf && isset($tf["text"])) {
            self::tfError($tf, true, "This review has been edited online since you downloaded this offline form, so for safety I am not replacing the online version.  If you want to override your online edits, add a line &ldquo;<code>==+==&nbsp;Version&nbsp;" . $rrow->reviewEditVersion . "</code>&rdquo; to your offline review form for paper #" . $req["paperId"] . " and upload the form again.");
            return 0;
        }
        if (isset($req["reviewerEmail"]) && $rrow
            && $rrow->email != $req["reviewerEmail"]
            && (!isset($req["reviewerName"]) || $req["reviewerName"] != trim("$rrow->firstName $rrow->lastName"))) {
            self::tfError($tf, true, "This review form claims to be written by " . htmlspecialchars($req["reviewerEmail"]) . " rather than the review&rsquo;s owner, " . Text::user_html($rrow) . ".  If you want to upload it anyway, remove the &ldquo;<code>==+==&nbsp;Reviewer</code>&rdquo; line from the form and try again.", "reviewerEmail");
            return 0;
        }
        if (isset($outofrange)) {
            if ($tf)
                foreach ($outofrange as $f)
                    self::tfError($tf, true, "Bad value for field \"" . $f->name_html . "\"", $f->id);
            else {
                foreach ($outofrange as $f)
                    $oor2[] = $f->name_html;
                Conf::msg_error("Bad values for " . commajoin($oor2) . ".  Please fix this and submit again.");
            }
            return 0;
        }
        if ($nokfields == 0 && $tf) {
            $tf["ignoredBlank"][] = "#" . $req["paperId"];
            return 0;
        }
        if (isset($provide)) {
            $w = "You did not set some mandatory fields.  Please set " . htmlspecialchars(commajoin($provide)) . " and submit again.";
            if ($tf)
                self::tfError($tf, false, $w);
            else
                $Conf->warnMsg($w);
            $req["unready"] = true;
        }
        return ($nokfields > 0);
    }

    function review_watch_callback($prow, $minic) {
        if ($minic->can_view_review($prow, $this->mailer_info["diff_view_score"], false)
            && ($p = HotCRPMailer::prepare_to($minic, $this->mailer_info["template"], $prow, $this->mailer_info))) {
            // Don't combine preparations unless you can see all submitted
            // reviewer identities
            if (!$minic->can_view_review_identity($prow, null, false))
                $p->unique_preparation = true;
            $this->mailer_preps[] = $p;
        }
    }

    function word_count($rrow) {
        $wc = 0;
        foreach ($this->forder as $field => $f)
            if ($rrow->$field
                && (!$f->round_mask || $f->is_round_visible($rrow))
                && $f->include_word_count())
                $wc += count_words($rrow->$field);
        return $wc;
    }

    function save_review($req, $rrow, $prow, $contact, &$tf = null) {
        global $Conf, $Opt;
        $newsubmit = get($req, "ready") && !get($req, "unready")
            && (!$rrow || !$rrow->reviewSubmitted);
        $submit = $newsubmit || ($rrow && $rrow->reviewSubmitted);
        $admin = $contact->allow_administer($prow);

        if (!$contact->timeReview($prow, $rrow)
            && (!isset($req['override']) || !$admin))
            return Conf::msg_error("The <a href='" . hoturl("deadlines") . "'>deadline</a> for entering this review has passed." . ($admin ? "  Select the “Override deadlines” checkbox and try again if you really want to override the deadline." : ""));

        $q = array();
        $diff_view_score = VIEWSCORE_FALSE;
        $wc = 0;
        foreach ($this->forder as $field => $f)
            if (isset($req[$field])
                && (!$f->round_mask || $f->is_round_visible($rrow))) {
                $fval = $req[$field];
                if ($f->has_options) {
                    if ($f->parse_is_empty($fval))
                        $fval = 0;
                    else if (!($fval = $f->parse_value($fval, false)))
                        continue;
                } else {
                    $fval = rtrim($fval);
                    if ($fval != "")
                        $fval .= "\n";
                    // Check for valid UTF-8; re-encode from Windows-1252 or Mac OS
                    $fval = convert_to_utf8($fval);
                    if ($f->include_word_count())
                        $wc += count_words($fval);
                }
                if ($rrow && strcmp($rrow->$field, $fval) != 0
                    && strcmp(cleannl($rrow->$field), cleannl($fval)) != 0)
                    $diff_view_score = max($diff_view_score, $f->view_score);
                $q[] = "$field='" . sqlq($fval) . "'";
            }

        // get the current time
        $now = time();
        if ($rrow && $rrow->reviewModified > 1 && $rrow->reviewModified > $now)
            $now = $rrow->reviewModified + 1;

        // potentially assign review ordinal (requires table locking since
        // mySQL is stupid)
        $locked = false;
        if ($newsubmit) {
            $diff_view_score = max($diff_view_score, VIEWSCORE_AUTHOR);
            $q[] = "reviewSubmitted=$now, reviewNeedsSubmit=0";
            if (!$rrow || !$rrow->reviewOrdinal) {
                $table_suffix = "";
                if ($Conf->au_seerev == Conf::AUSEEREV_TAGS)
                    $table_suffix = ", PaperTag read";
                $result = $Conf->qe("lock tables PaperReview write" . $table_suffix);
                if (!$result)
                    return $result;
                $locked = true;
                $result = $Conf->qe("select coalesce(max(reviewOrdinal), 0) from PaperReview where paperId=$prow->paperId group by paperId");
                if ($result) {
                    $crow = edb_row($result);
                    $q[] = "reviewOrdinal=coalesce(reviewOrdinal, " . ($crow[0] + 1) . ")";
                }
                Dbl::free($result);
                $q[] = "timeDisplayed=$now";
            }
        }

        // check whether used a review token
        $usedReviewToken = $contact->review_token_cid($prow, $rrow);

        // blind? reviewer type? edit version?
        $reviewBlind = $Conf->is_review_blind(!!get($req, "blind"));
        if ($rrow && $reviewBlind != $rrow->reviewBlind)
            $diff_view_score = max($diff_view_score, VIEWSCORE_ADMINONLY);
        $q[] = "reviewBlind=" . ($reviewBlind ? 1 : 0);
        if ($rrow && $rrow->reviewType == REVIEW_EXTERNAL
            && $contact->contactId == $rrow->contactId
            && $contact->isPC && !$usedReviewToken)
            $q[] = "reviewType=" . REVIEW_PC;
        if ($rrow && $diff_view_score > VIEWSCORE_FALSE
            && isset($req["version"]) && ctype_digit($req["version"])
            && $req["version"] > defval($rrow, "reviewEditVersion"))
            $q[] = "reviewEditVersion=" . ($req["version"] + 0);
        if ($diff_view_score > VIEWSCORE_FALSE && $Conf->sversion >= 98)
            $q[] = "reviewWordCount=" . $wc;
        if (isset($req["reviewFormat"]) && $Conf->sversion >= 104
            && opt("formatInfo")) {
            $fmt = null;
            foreach ($Opt["formatInfo"] as $k => $f)
                if (get($f, "name") && strcasecmp($f["name"], $req["reviewFormat"]) == 0)
                    $fmt = (int) $k;
            if (!$fmt && $req["reviewFormat"]
                && preg_match('/\A(?:plain\s*)?(?:text)?\z/i', $f["reviewFormat"]))
                $fmt = 0;
            $q[] = "reviewFormat=" . ($fmt === null ? "null" : $fmt);
        }

        // notification
        $notification_bound = $now - 10800;
        $notify = $notify_author = false;
        if (!$rrow || $diff_view_score > VIEWSCORE_FALSE) {
            $q[] = "reviewModified=" . $now;
            // do not notify on updates within 3 hours
            if ($submit && $diff_view_score > VIEWSCORE_ADMINONLY) {
                if (!$rrow || !$rrow->reviewNotified
                    || $rrow->reviewNotified < $notification_bound)
                    $q[] = $notify = "reviewNotified=" . $now;
                if ((!$rrow || !$rrow->reviewAuthorNotified
                     || $rrow->reviewAuthorNotified < $notification_bound)
                    && $diff_view_score >= VIEWSCORE_AUTHOR
                    && Contact::can_some_author_view_submitted_review($prow))
                    $q[] = $notify_author = "reviewAuthorNotified=" . $now;
            }
        }

        // actually affect database
        if ($rrow) {
            $result = $Conf->qe("update PaperReview set " . join(", ", $q) . " where reviewId=$rrow->reviewId");
            $reviewId = $rrow->reviewId;
            $contactId = $rrow->contactId;
        } else {
            $result = Dbl::qe_raw("insert into PaperReview set paperId=$prow->paperId, contactId=$contact->contactId, reviewType=" . REVIEW_PC . ", requestedBy=$contact->contactId, reviewRound=" . $Conf->current_round() . ", " . join(", ", $q));
            $reviewId = $result ? $result->insert_id : null;
            $contactId = $contact->contactId;
        }

        // unlock tables even if problem
        if ($locked)
            $Conf->qe("unlock tables");
        if (!$result)
            return $result;

        // update caches
        Contact::update_rights();

        // look up review ID
        if (!$reviewId)
            return $reviewId;
        $req['reviewId'] = $reviewId;

        // log updates -- but not if review token is used
        if (!$usedReviewToken && $diff_view_score > VIEWSCORE_FALSE) {
            $text = "Review $reviewId ";
            if ($rrow && $contact->contactId != $rrow->contactId)
                $text .= "by $rrow->email ";
            $text .= $newsubmit ? "submitted" : ($submit ? "updated" : "saved draft");
            $contact->log_activity($text, $prow);
        }

        // potentially email chair, reviewers, and authors
        if ($submit)
            $rrow = $Conf->reviewRow(["reviewId" => $reviewId]);
        if ($submit && ($notify || $notify_author) && $rrow) {
            $tmpl = $newsubmit ? "@reviewsubmit" : "@reviewupdate";
            $submitter = $contact;
            if ($contactId != $submitter->contactId)
                $submitter = Contact::find_by_id($contactId);

            // construct mail
            $this->mailer_info = array("template" => $tmpl,
                    "rrow" => $rrow,
                    "reviewer_contact" => $submitter,
                    "reviewNumber" => $prow->paperId . unparseReviewOrdinal($rrow->reviewOrdinal),
                    "check_function" => "HotCRPMailer::check_can_view_review",
                    "diff_view_score" => $diff_view_score);
            $this->mailer_preps = array();
            if ($Conf->timeEmailChairAboutReview())
                HotCRPMailer::send_manager($tmpl, $prow, $this->mailer_info);
            $prow->notify(WATCHTYPE_REVIEW, array($this, "review_watch_callback"), $contact);
            if (count($this->mailer_preps))
                HotCRPMailer::send_combined_preparations($this->mailer_preps);
            unset($this->mailer_info, $this->mailer_preps);
        }

        // if external, forgive the requestor from finishing their review
        if ($rrow && $rrow->reviewType < REVIEW_SECONDARY && $rrow->requestedBy && $submit)
            $Conf->q("update PaperReview set reviewNeedsSubmit=0 where paperId=$prow->paperId and contactId=$rrow->requestedBy and reviewType=" . REVIEW_SECONDARY . " and reviewSubmitted is null");

        if ($tf !== null) {
            $what = "#$prow->paperId" . ($rrow && $rrow->reviewSubmitted ? unparseReviewOrdinal($rrow->reviewOrdinal) : "");
            if ($newsubmit)
                $tf["newlySubmitted"][] = $what;
            else if ($diff_view_score > VIEWSCORE_FALSE && $submit)
                $tf["updated"][] = $what;
            else if ($diff_view_score > VIEWSCORE_FALSE)
                $tf["savedDraft"][] = $what;
            else
                $tf["unchanged"][] = $what;
            if ($notify_author)
                $tf["authorNotified"][] = $what;
        }

        return $result;
    }

    private function reviewer_error($req, &$tf, $msg = null) {
        if (!$msg)
            $msg = "Can’t submit a review for this reviewer.";
        $msg = htmlspecialchars($req["reviewerEmail"]) . ": " . $msg;
        return $this->tfError($tf, true, $msg . "<br /><span class=\"hint\">You may be mistakenly submitting a review form intended for someone else. Remove the form’s “Reviewer:” line to enter your own review.</span>", "reviewerEmail");
    }

    function check_save_review($req, &$tf, $contact) {
        global $Conf;

        // look up reviewer
        $Reviewer = $contact;
        if (isset($req["reviewerEmail"])
            && strcasecmp($req["reviewerEmail"], $contact->email) != 0
            && !($Reviewer = Contact::find_by_email($req["reviewerEmail"])))
            return $this->reviewer_error($req, $tf, $contact->privChair ? "No such user." : null);

        // look up paper & review rows, check review permission
        if (!($prow = $Conf->paperRow($req["paperId"], $contact, $whyNot)))
            return $this->tfError($tf, true, whyNotText($whyNot, "review"));
        $rrow_args = ["paperId" => $prow->paperId, "first" => true,
            "contactId" => $Reviewer->contactId, "rev_tokens" => $contact->review_tokens()];
        $rrow = $Conf->reviewRow($rrow_args);
        $new_rrid = false;
        if ($contact !== $Reviewer && !$rrow) {
            if (!$contact->can_create_review_from($prow, $Reviewer))
                return $this->reviewer_error($req, $tf);
            $new_rrid = $contact->assign_review($prow->paperId, $Reviewer->contactId, $Reviewer->isPC ? REVIEW_PC : REVIEW_EXTERNAL);
            if (!$new_rrid)
                return $this->tfError($tf, true, "Internal error while creating review.");
            $rrow = $Conf->reviewRow($rrow_args);
        }
        if (($whyNot = $contact->perm_submit_review($prow, $rrow))) {
            if ($contact === $Reviewer || $contact->can_view_review_identity($prow, $rrow))
                return $this->tfError($tf, true, whyNotText($whyNot, "review"));
            else
                return $this->reviewer_error($req, $tf);
        }

        // actually check review and save
        if ($this->checkRequestFields($req, $rrow, $tf)) {
            $this->save_review($req, $rrow, $prow, $contact, $tf);
            return true;
        } else {
            if ($new_rrid)
                $contact->assign_review($prow->paperId, $Reviewer->contactId, 0);
            return false;
        }
    }


    static function textFormHeader($type) {
        $x = "==+== " . Conf::$gShortName . " Paper Review Form" . ($type === true ? "s" : "") . "\n";
        $x .= "==-== DO NOT CHANGE LINES THAT START WITH \"==+==\" UNLESS DIRECTED!
==-== For further guidance, or to upload this file when you are done, go to:
==-== " . hoturl_absolute_raw("offline") . "\n\n";
        return $x;
    }

    static function cleanDescription($d) {
        $d = preg_replace('|\s*<\s*br\s*/?\s*>\s*(?:<\s*/\s*br\s*>\s*)?|si', "\n", $d);
        $d = preg_replace('|\s*<\s*li\s*>|si', "\n* ", $d);
        $d = preg_replace(',<(?:[^"\'>]|".*?"|\'.*?\')*>,s', "", $d);
        return html_entity_decode($d, ENT_QUOTES, "UTF-8");
    }

    static function update_review_author_seen() {
        global $Conf, $Now;
        if (self::$review_author_seen && $Conf->sversion >= 92) {
            Dbl::qe("update PaperReview set reviewAuthorSeen=coalesce(reviewAuthorSeen,$Now) where reviewId ?a", self::$review_author_seen);
            self::$review_author_seen = null;
        }
    }

    static private function check_review_author_seen($prow, $rrow, $contact,
                                                     $no_update = false) {
        global $Now;
        if ($rrow && !get($rrow, "reviewAuthorSeen")
            && $contact->act_author_view($prow)
            && !$contact->is_actas_user()) {
            $rrow->reviewAuthorSeen = $Now;
            if (!$no_update) {
                if (!self::$review_author_seen) {
                    register_shutdown_function("ReviewForm::update_review_author_seen");
                    self::$review_author_seen = array();
                }
                self::$review_author_seen[] = $rrow->reviewId;
            }
        }
    }

    function textForm($prow, $rrow, $contact, $req = null) {
        global $Conf;

        $rrow_contactId = 0;
        if (isset($rrow) && isset($rrow->reviewContactId))
            $rrow_contactId = $rrow->reviewContactId;
        else if (isset($rrow) && isset($rrow->contactId))
            $rrow_contactId = $rrow->contactId;
        $myReview = !$rrow || $rrow_contactId == 0 || $rrow_contactId == $contact->contactId;
        $revViewScore = $prow ? $contact->view_score_bound($prow, $rrow) : $contact->permissive_view_score_bound();
        self::check_review_author_seen($prow, $rrow, $contact);
        $viewable_identity = !$prow || $contact->can_view_review_identity($prow, $rrow, true);

        $x = "==+== =====================================================================\n";
        //$x .= "$prow->paperId:$myReview:$revViewScore:$rrow->contactId:$rrow->reviewContactId;;$prow->conflictType;;$prow->reviewType\n";

        $x .= "==+== Begin Review";
        if ($req && isset($req['reviewOrdinal']))
            $x .= " #" . $prow->paperId . unparseReviewOrdinal($req['reviewOrdinal']);
        else if ($rrow && isset($rrow->reviewOrdinal))
            $x .= " #" . $prow->paperId . unparseReviewOrdinal($rrow->reviewOrdinal);
        $x .= "\n";
        if ($rrow && defval($rrow, "reviewEditVersion") && $viewable_identity)
            $x .= "==+== Version " . $rrow->reviewEditVersion . "\n";
        if (!$prow || $viewable_identity) {
            if ($rrow && isset($rrow->reviewEmail))
                $x .= "==+== Reviewer: " . Text::user_text($rrow->reviewFirstName, $rrow->reviewLastName, $rrow->reviewEmail) . "\n";
            else if ($rrow && isset($rrow->email))
                $x .= "==+== Reviewer: " . Text::user_text($rrow) . "\n";
            else
                $x .= "==+== Reviewer: " . Text::user_text($contact) . "\n";
        }
        if ($rrow && $rrow->reviewModified > 1 && $contact->can_view_review_time($prow, $rrow))
            $x .= "==-== Updated " . $Conf->printableTime($rrow->reviewModified) . "\n";

        if ($prow)
            $x .= "\n==+== Paper #$prow->paperId\n"
                . prefix_word_wrap("==-== Title: ", $prow->title, "==-==        ")
                . "\n";
        else
            $x .= "\n==+== Paper Number\n\n(Enter paper number here)\n\n";

        if ($viewable_identity) {
            $x .= "==+== Review Readiness
==-== Enter \"Ready\" if the review is ready for others to see:

Ready\n";
            if ($Conf->review_blindness() == Conf::BLIND_OPTIONAL) {
                $blind = "Anonymous";
                if ($rrow && !$rrow->reviewBlind)
                    $blind = "Open";
                $x .= "\n==+== Review Anonymity
==-== " . Conf::$gShortName . " allows either anonymous or open review.
==-== Enter \"Open\" if you want to expose your name to authors:

$blind\n";
            }
        }

        $i = 0;
        $numericMessage = 0;
        $format_description = self::format_description($rrow, true);
        foreach ($this->forder as $field => $f) {
            $i++;
            if ($f->view_score <= $revViewScore
                || ($f->round_mask && !$f->is_round_visible($rrow)))
                continue;

            $fval = "";
            if ($req && isset($req[$field]))
                $fval = rtrim($req[$field]);
            else if ($rrow != null && isset($rrow->$field)) {
                if ($f->has_options)
                    $fval = $f->unparse_value($rrow->$field);
                else
                    $fval = rtrim(str_replace("\r\n", "\n", $rrow->$field));
            }
            if ($f->has_options && isset($f->options[$fval]))
                $fval = "$fval. " . $f->options[$fval];
            else if (!$fval)
                $fval = "";

            $x .= "\n==+== " . chr(64 + $i) . ". " . $f->name;
            if ($f->view_score < VIEWSCORE_REVIEWERONLY)
                $x .= " (secret)";
            else if ($f->view_score < VIEWSCORE_PC)
                $x .= " (shown only to chairs)";
            else if ($f->view_score < VIEWSCORE_AUTHOR)
                $x .= " (hidden from authors)";
            $x .= "\n";
            if ($f->description) {
                $d = cleannl($f->description);
                if (strpbrk($d, "&<") !== false)
                    $d = self::cleanDescription($d);
                $x .= prefix_word_wrap("==-==    ", $d, "==-==    ");
            }
            if ($f->has_options) {
                $first = true;
                foreach ($f->options as $num => $value) {
                    $y = ($first ? "==-== Choices: " : "==-==          ") . "$num. ";
                    $x .= prefix_word_wrap($y, $value, str_pad("==-==", strlen($y)));
                    $first = false;
                }
                if ($f->allow_empty)
                    $x .= "==-==          No entry\n==-== Enter your choice:\n";
                else if ($f->option_letter)
                    $x .= "==-== Enter the letter of your choice:\n";
                else
                    $x .= "==-== Enter the number of your choice:\n";
                if ($fval == "" && $f->allow_empty)
                    $fval = "No entry";
                else
                    $fval = "(Your choice here)";
            } else if ($format_description)
                $x .= prefix_word_wrap("==-== ", $format_description, "==-== ");
            $x .= "\n" . preg_replace("/^==\\+==/m", "\\==+==", $fval) . "\n";
        }
        return $x . "\n==+== Scratchpad (for unsaved private notes)\n\n==+== End Review\n";
    }

    function pretty_text($prow, $rrow, $contact, $no_update_review_author_seen = false) {
        global $Conf;
        assert($prow !== null && $rrow !== null);

        $rrow_contactId = get($rrow, "reviewContactId") ? : (get($rrow, "contactId") ? : 0);
        $revViewScore = $contact->view_score_bound($prow, $rrow);
        self::check_review_author_seen($prow, $rrow, $contact, $no_update_review_author_seen);

        $x = "===========================================================================\n";
        $n = Conf::$gShortName . " Review";
        if (get($rrow, "reviewOrdinal"))
            $n .= " #" . $prow->paperId . unparseReviewOrdinal($rrow->reviewOrdinal);
        $x .= center_word_wrap($n);
        if ($rrow->reviewModified > 1 && $contact->can_view_review_time($prow, $rrow)) {
            $n = "Updated " . $Conf->printableTime($rrow->reviewModified);
            $x .= center_word_wrap($n);
        }
        $x .= "---------------------------------------------------------------------------\n";
        $x .= $prow->pretty_text_title();
        if ($contact->can_view_review_identity($prow, $rrow, false)) {
            if (isset($rrow->reviewFirstName))
                $n = Text::user_text($rrow->reviewFirstName, $rrow->reviewLastName, $rrow->reviewEmail);
            else if (isset($rrow->lastName))
                $n = Text::user_text($rrow);
            else
                $n = null;
            if ($n)
                $x .= prefix_word_wrap("Reviewer: ", $n, $prow->pretty_text_title_indent());
        }
        $x .= "---------------------------------------------------------------------------\n\n";

        $i = 0;
        $lastNumeric = null;
        foreach ($this->forder as $field => $f) {
            $i++;
            if ($f->view_score <= $revViewScore
                || ($f->round_mask && !$f->is_round_visible($rrow)))
                continue;

            $fval = "";
            if (isset($rrow->$field)) {
                if ($f->has_options)
                    $fval = $f->unparse_value($rrow->$field);
                else
                    $fval = rtrim(str_replace("\r\n", "\n", $rrow->$field));
            }
            if ($fval == "")
                continue;

            if ($f->has_options) {
                $y = defval($f->options, $fval, "");
                $sn = $f->name . ":";
                /* "(1-" . count($f->options) . "):"; */
                if ($lastNumeric === false)
                    $x .= "\n";
                if (strlen($sn) > 38 + strlen($fval))
                    $x .= $sn . "\n" . prefix_word_wrap($fval . ". ", $y, 39 + strlen($fval));
                else
                    $x .= prefix_word_wrap($sn . " " . $fval . ". ", $y, 39 + strlen($fval));
                $lastNumeric = true;
            } else {
                $n = "===== " . $f->name . " =====";
                if ($lastNumeric !== null)
                    $x .= "\n";
                $x .= center_word_wrap($n);
                $x .= "\n" . preg_replace("/^==\\+==/m", "\\==+==", $fval) . "\n";
                $lastNumeric = false;
            }
        }
        return $x;
    }

    function garbageMessage(&$tf, $lineno, &$garbage) {
        if (isset($garbage))
            self::tfError($tf, false, "Review form appears to begin with garbage; ignoring it.", $lineno);
        unset($garbage);
    }

    function beginTextForm($filename, $printFilename) {
        if (($contents = file_get_contents($filename)) === false)
            return null;
        return array('text' => cleannl($contents), 'filename' => $printFilename,
                     'lineno' => 0, 'err' => array(), 'confirm' => array());
    }

    function parseTextForm(&$tf) {
        $text = $tf['text'];
        $lineno = $tf['lineno'];
        $tf['firstLineno'] = $lineno + 1;
        $tf['fieldLineno'] = array();
        $req = array();
        if (isset($_REQUEST["override"]))
            $req["override"] = $_REQUEST["override"];

        $mode = 0;
        $nfields = 0;
        $field = 0;
        $anyDirectives = 0;

        while ($text != "") {
            $pos = strpos($text, "\n");
            $line = ($pos === FALSE ? $text : substr($text, 0, $pos + 1));
            $lineno++;

            if (substr($line, 0, 6) == "==+== ") {
                // make sure we record that we saw the last field
                if ($mode && $field != null && !isset($req[$field]))
                    $req[$field] = "";

                $anyDirectives++;
                if (preg_match('{\A==\+==\s+(.*?)\s+(Paper Review(?: Form)?s?)\s*\z}', $line, $m)
                    && $m[1] != Conf::$gShortName) {
                    $this->garbageMessage($tf, $lineno, $garbage);
                    self::tfError($tf, true, "Ignoring review form, which appears to be for a different conference.<br />(If this message is in error, replace the line that reads “<code>" . htmlspecialchars(rtrim($line)) . "</code>” with “<code>==+== " . htmlspecialchars(Conf::$gShortName) . " " . $m[2] . "</code>” and upload again.)", $lineno);
                    return null;
                } else if (preg_match('/^==\+== Begin Review/i', $line)) {
                    if ($nfields > 0)
                        break;
                } else if (preg_match('/^==\+== Paper #?(\d+)/i', $line, $match)) {
                    if ($nfields > 0)
                        break;
                    $req['paperId'] = $tf['paperId'] = $match[1];
                    $req['blind'] = 1;
                    $tf['firstLineno'] = $lineno;
                } else if (preg_match('/^==\+== Reviewer:\s*(.*)\s*<(\S+?)>/', $line, $match)) {
                    $tf["fieldLineno"]["reviewerEmail"] = $lineno;
                    $req["reviewerName"] = $match[1];
                    $req["reviewerEmail"] = $match[2];
                } else if (preg_match('/^==\+== Paper (Number|\#)\s*$/i', $line)) {
                    if ($nfields > 0)
                        break;
                    $field = "paperNumber";
                    $tf["fieldLineno"][$field] = $lineno;
                    $mode = 1;
                    $req['blind'] = 1;
                    $tf['firstLineno'] = $lineno;
                } else if (preg_match('/^==\+== Submit Review\s*$/i', $line)
                           || preg_match('/^==\+== Review Ready\s*$/i', $line)) {
                    $req['ready'] = true;
                } else if (preg_match('/^==\+== Open Review\s*$/i', $line)) {
                    $req['blind'] = 0;
                } else if (preg_match('/^==\+== Version\s*(\d+)$/i', $line, $match)) {
                    if (defval($req, "version", 0) < $match[1])
                        $req['version'] = $match[1];
                } else if (preg_match('/^==\+== Review Readiness\s*/i', $line)) {
                    $field = "readiness";
                    $mode = 1;
                } else if (preg_match('/^==\+== Review Anonymity\s*/i', $line)) {
                    $field = "anonymity";
                    $mode = 1;
                } else if (preg_match('/^==\+== Review Format\s*/i', $line)) {
                    $field = "reviewFormat";
                    $mode = 1;
                } else if (preg_match('/^==\+== [A-Z]\.\s*(.*?)\s*$/', $line, $match)) {
                    $fname = $match[1];
                    if (!isset($this->fieldName[strtolower($fname)]))
                        $fname = preg_replace('/\s*\((hidden from authors|PC only|shown only to chairs|secret)\)\z/', "", $fname);
                    $fn =& $this->fieldName[strtolower($fname)];
                    if (isset($fn)) {
                        $field = $fn;
                        $tf['fieldLineno'][$fn] = $lineno;
                        $nfields++;
                    } else {
                        $this->garbageMessage($tf, $lineno, $garbage);
                        self::tfError($tf, true, "Review field &ldquo;" . htmlentities($fname) . "&rdquo; is not used for " . htmlspecialchars(Conf::$gShortName) . " reviews.  Ignoring this section.", $lineno);
                        $field = null;
                    }
                    $mode = 1;
                } else {
                    $field = null;
                    $mode = 1;
                }
            } else if ($mode < 2 && (substr($line, 0, 5) == "==-==" || ltrim($line) == ""))
                /* ignore line */;
            else {
                if ($mode == 0) {
                    $garbage = $line;
                    $field = null;
                }
                if ($field != null)
                    $req[$field] = defval($req, $field, "") . $line;
                $mode = 2;
            }

            $text = substr($text, strlen($line));
        }

        if ($nfields == 0 && $tf['firstLineno'] == 1)
            self::tfError($tf, true, "That didn&rsquo;t appear to be a review form; I was not able to extract any information from it.  Please check its formatting and try again.", $lineno);

        $tf['text'] = $text;
        $tf['lineno'] = $lineno - 1;

        if (isset($req["readiness"]))
            $req["ready"] = strcasecmp(trim($req["readiness"]), "Ready") == 0;
        if (isset($req["anonymity"]))
            $req["blind"] = strcasecmp(trim($req["anonymity"]), "Open") != 0;
        if (isset($req["reviewFormat"]))
            $req["reviewFormat"] = trim($req["reviewFormat"]);

        if (isset($req["paperId"]))
            /* OK */;
        else if (isset($req["paperNumber"])
                 && ($pid = cvtint(trim($req["paperNumber"]), -1)) > 0)
            $req["paperId"] = $tf["paperId"] = $pid;
        else if ($nfields > 0) {
            self::tfError($tf, true, "This review form doesn’t report which paper number it is for.  Make sure you’ve entered the paper number in the right place and try again.", defval($tf["fieldLineno"], "paperNumber", $lineno));
            $nfields = 0;
        }

        if ($nfields == 0 && $text) // try again
            return $this->parseTextForm($tf);
        else if ($nfields == 0)
            return null;
        else
            return $req;
    }

    private static function _paperCommaJoin($pl, $a, $single) {
        while (preg_match('/\b(\w+)\*/', $pl, $m))
            $pl = preg_replace('/\b' . $m[1] . '\*/', pluralx(count($a), $m[1]), $pl);
        if ($single)
            return preg_replace('/\|.*/', "", $pl);
        $pids = array();
        foreach ($a as &$x)
            if (preg_match('/\A(#?)(\d+)([A-Z]*)\z/', $x, $m)) {
                $x = "<a href=\"" . hoturl("paper", ["p" => $m[2], "anchor" => $m[3] ? "r$m[2]$m[3]" : null]) . "\">" . $x . "</a>";
                $pids[] = $m[2];
            }
        $t = str_replace("|", "", $pl) . commajoin($a);
        if (count($pids) > 1)
            $t = '<span class="has_hotcrp_list" data-hotcrp-list="p/s/' . join("+", $pids) . '">' . $t . '</span>';
        return $t;
    }

    function textFormMessages(&$tf) {
        global $Conf;

        if (isset($tf["err"]) && count($tf["err"]) > 0) {
            $Conf->msg(defval($tf, 'anyErrors') ? "merror" : "warning",
                       "There were " . (defval($tf, 'anyErrors') && defval($tf, 'anyWarnings') ? "errors and warnings" : (defval($tf, 'anyErrors') ? "errors" : "warnings")) . " while parsing the uploaded reviews file. <div class='parseerr'><p>" . join("</p>\n<p>", $tf['err']) . "</p></div>");
        }

        $confirm = array();
        $single = get($tf, "singlePaper");
        if (isset($tf["confirm"]) && count($tf["confirm"]) > 0)
            $confirm = array_merge($confirm, $tf["confirm"]);
        if (isset($tf["newlySubmitted"]) && count($tf["newlySubmitted"]) > 0)
            $confirm[] = self::_paperCommaJoin("Review*| ", $tf["newlySubmitted"], $single) . " submitted.";
        if (isset($tf["updated"]) && count($tf["updated"]) > 0)
            $confirm[] = self::_paperCommaJoin("Review*| ", $tf["updated"], $single) . " updated.";
        if (isset($tf["savedDraft"]) && count($tf["savedDraft"]) > 0) {
            if ($single)
                $confirm[] = "Draft review saved. However, this version is marked as not ready for others to see. Please finish the review and submit again.";
            else
                $confirm[] = self::_paperCommaJoin("Draft review*| for paper* ", $tf["savedDraft"], $single) . " saved.";
        }
        $nconfirm = count($confirm);
        if (isset($tf["authorNotified"]) && count($tf["authorNotified"]) > 0)
            $confirm[] = self::_paperCommaJoin("Notified authors| about updated review*| ", $tf["authorNotified"], $single) . ".";
        if (isset($tf["unchanged"]) && count($tf["unchanged"]) > 0)
            $confirm[] = self::_paperCommaJoin("Review*| ", $tf["unchanged"], $single) . " unchanged.";
        if (isset($tf["ignoredBlank"]) && count($tf["ignoredBlank"]) > 0)
            $confirm[] = self::_paperCommaJoin("Ignored blank review form*| ", $tf["ignoredBlank"], $single) . ".";
        // self::tfError($tf, false, "Ignored blank " . pluralx(count($tf["ignoredBlank"]), "review form") . " for " . self::_paperCommaJoin("review form* for paper*", $tf["ignoredBlank"]) . ".");
        if (count($confirm))
            $Conf->msg($nconfirm ? "confirm" : "warning", "<div class='parseerr'><p>" . join("</p>\n<p>", $confirm) . "</p></div>");
    }

    function webGuidanceRows($revViewScore, $extraclass="") {
        $x = '';

        foreach ($this->forder as $field => $f) {
            if ($f->view_score <= $revViewScore
                || (!$f->description && !$f->has_options))
                continue;

            $x .= "<tr class='rev_$field'>\n";
            $x .= "  <td class='caption rev_$field$extraclass'>";
            $x .= $f->name_html . "</td>\n";

            $x .= "  <td class='entry rev_$field$extraclass'>";
            if ($f->description)
                $x .= "<div class='rev_description'>" . $f->description . "</div>";
            if ($f->has_options) {
                $x .= "<div class='rev_options'>Choices are:";
                foreach ($f->options as $num => $val)
                    $x .= "<br />\n" . $f->unparse_value($num, ReviewField::VALUE_REV_NUM) . " " . htmlspecialchars($val);
                $x .= "</div>";
            }

            $x .= "</td>\n</tr>\n";
            $extraclass = "";
        }

        return $x;
    }

    public function set_can_view_ratings($prow, $rrows, $contact) {
        global $Conf;
        $my_rrow = null;
        foreach ($rrows as $rrow)
            if (!isset($rrow->allRatings) || $rrow->allRatings === "")
                $rrow->canViewRatings = false;
            else if ($rrow->contactId != $contact->contactId
                     || $contact->can_administer($prow)
                     || $Conf->timePCViewAllReviews()
                     || strpos($rrow->allRatings, ",") !== false)
                $rrow->canViewRatings = true;
            else
                $my_rrow = $rrow;
        if ($my_rrow) {
            // Do not show rating counts if rater identity is unambiguous.
            // See also PaperSearch::_clauseTermSetRating.
            $nsubraters = 0;
            $rateset = $Conf->setting("rev_ratings");
            foreach ($rrows as $rrow)
                if ($rrow->reviewNeedsSubmit == 0
                    && $rrow->contactId != $contact->contactId
                    && ($rateset == REV_RATINGS_PC_EXTERNAL
                        || ($rateset == REV_RATINGS_PC && $rrow->reviewType > REVIEW_EXTERNAL)))
                    ++$nsubraters;
            $my_rrow->canViewRatings = $nsubraters >= 2;
        }
    }

    private function _review_buttons($prow, $rrow, $type, $reviewPostLink) {
        global $Conf, $Me;
        $buttons = array();

        // refuse?
        if ($type == "top" && $rrow && !$rrow->reviewModified
            && $rrow->reviewType < REVIEW_SECONDARY) {
            $buttons[] = Ht::submit("accept", "Accept review", array("class" => "b"));
            $buttons[] = Ht::button("Decline review",
                                    array("class" => "b",
                                          "onclick" => "popup(this,'ref',0)"));
            // Also see $_REQUEST["refuse"] case in review.php.
            $Conf->footerHtml("<div id='popup_ref' class='popupc'>"
    . Ht::form_div($reviewPostLink)
    . Ht::hidden("refuse", "refuse")
    . "<p style='margin:0 0 0.3em'>Select “Decline review” to decline this review. Thank you for keeping us informed.</p>"
    . Ht::textarea("reason", null,
                   array("id" => "refusereviewreason", "rows" => 3, "cols" => 40,
                         "placeholder" => "Optional explanation", "spellcheck" => "true"))
    . '<div class="popup-actions">'
    . Ht::submit("Decline review", ["class" => "popup-btn"])
    . Ht::js_button("Cancel", "popup(null,'ref',1)", ["class" => "popup-btn"])
    . "</div></div></form></div>", "declinereviewform");
            $buttons[] = "";
        }

        $submitted = $rrow && $rrow->reviewSubmitted;
        if (!$Conf->time_review($rrow, $Me->act_pc($prow, true), true)) {
            $whyNot = array("deadline" => ($rrow && $rrow->reviewType < REVIEW_PC ? "extrev_hard" : "pcrev_hard"));
            $override_text = whyNotText($whyNot, "review");
            if (!$submitted) {
                $buttons[] = array(Ht::js_button("Submit review", "override_deadlines(this)", array("class" => "bb", "data-override-text" => $override_text, "data-override-submit" => "submitreview")), "(admin only)");
                $buttons[] = array(Ht::js_button("Save as draft", "override_deadlines(this)", array("data-override-text" => $override_text, "data-override-submit" => "savedraft")), "(admin only)");
            } else
                $buttons[] = array(Ht::js_button("Save changes", "override_deadlines(this)", array("class" => "bb", "data-override-text" => $override_text, "data-override-submit" => "submitreview")), "(admin only)");
        } else if (!$submitted) {
            $buttons[] = Ht::submit("submitreview", "Submit review", array("class" => "bb"));
            $buttons[] = Ht::submit("savedraft", "Save as draft");
        } else
            $buttons[] = Ht::submit("submitreview", "Save changes", array("class" => "bb"));

        if ($rrow && $type == "bottom" && $Me->allow_administer($prow)) {
            $buttons[] = "";
            if ($submitted)
                $buttons[] = array(Ht::submit("unsubmitreview", "Unsubmit review"), "(admin only)");
            $buttons[] = array(Ht::js_button("Delete review", "popup(this,'d',0)"), "(admin only)");
            $Conf->footerHtml("<div id='popup_d' class='popupc'>
  <p>Be careful: This will permanently delete all information about this
  review assignment from the database and <strong>cannot be
  undone</strong>.</p>
  " . Ht::form_div($reviewPostLink, array("divclass" => "popup-actions"))
    . Ht::submit("deletereview", "Delete review", ["class" => "popup-btn dangerous"])
    . Ht::js_button("Cancel", "popup(null,'d',1)", ["class" => "popup-btn"])
    . "</div></form></div>");
        }

        return $buttons;
    }

    function show($prow, $rrows, $rrow, &$options) {
        global $Conf, $Opt, $Me, $useRequest;

        if (!$options)
            $options = array();
        $editmode = defval($options, "edit", false);

        $reviewOrdinal = unparseReviewOrdinal($rrow);
        self::check_review_author_seen($prow, $rrow, $Me);

        if (!$editmode) {
            $rj = $this->unparse_review_json($prow, $rrow, $Me);
            if (get($options, "editmessage"))
                $rj->message_html = $options["editmessage"];
            $Conf->echoScript("review_form.add_review(" . json_encode($rj) . ");\n");
            return;
        }

        // From here on, edit mode.
        $forceShow = $Me->is_admin_force() ? "&amp;forceShow=1" : "";
        $reviewLinkArgs = "p=$prow->paperId" . ($rrow ? "&amp;r=$reviewOrdinal" : "") . "&amp;m=re" . $forceShow;
        $reviewPostLink = hoturl_post("review", $reviewLinkArgs);
        $reviewDownloadLink = hoturl("review", $reviewLinkArgs . "&amp;downloadForm=1" . $forceShow);

        echo Ht::form($reviewPostLink, array("class" => "editrevform")),
            '<div class="aahc">',
            Ht::hidden_default_submit("default", "");
        if ($rrow)
            echo Ht::hidden("version", defval($rrow, "reviewEditVersion", 0) + 1);
        echo '<div class="revcard" id="r', $reviewOrdinal, '"><div class="revcard_head">';

        // Links
        if ($rrow) {
            echo '<div class="floatright"><a href="' . hoturl("review", "r=$reviewOrdinal&amp;text=1" . $forceShow) . '" class="xx">',
                Ht::img("txt.png", "[Text]", "b"),
                "&nbsp;<u>Plain text</u></a>",
                "</div>";
        }

        echo "<h3>";
        if ($rrow) {
            echo '<a href="', hoturl("review", "r=$reviewOrdinal" . $forceShow), '" class="q">Edit Review';
            if ($rrow->reviewSubmitted)
                echo "&nbsp;#", $reviewOrdinal;
            echo "</a>";
        } else
            echo "Write Review";
        echo "</h3>\n";

        $open = $sep = " <span class='revinfo'>";
        $xsep = " <span class='barsep'>·</span> ";
        $showtoken = $rrow && $Me->review_token_cid($prow, $rrow);
        $type = "";
        if ($rrow && $Me->can_view_review_round($prow, $rrow, null)) {
            $type = review_type_icon($rrow->reviewType);
            if ($rrow->reviewRound > 0 && $Me->can_view_review_round($prow, $rrow, null))
                $type .= "&nbsp;<span class=\"revround\" title=\"Review round\">"
                    . htmlspecialchars($Conf->round_name($rrow->reviewRound, true))
                    . "</span>";
        }
        if ($rrow && $Me->can_view_review_identity($prow, $rrow, null)
            && (!$showtoken || !Contact::is_anonymous_email($rrow->email))) {
            echo $sep, ($rrow->reviewBlind ? "[" : ""), Text::user_html($rrow),
                ($rrow->reviewBlind ? "]" : ""), " &nbsp;", $type;
            $sep = $xsep;
        } else if ($type) {
            echo $sep, $type;
            $sep = $xsep;
        }
        if ($showtoken) {
            echo $sep, "Review token ", encode_token((int) $rrow->reviewToken);
            $sep = $xsep;
        }
        if ($rrow && $rrow->reviewModified > 1 && $Me->can_view_review_time($prow, $rrow)) {
            echo $sep, "Updated ", $Conf->printableTime($rrow->reviewModified);
            $sep = $xsep;
        }
        if ($sep != $open)
            echo "</span>\n";

        if (defval($options, "editmessage"))
            echo "<div class='hint'>", defval($options, "editmessage"), "</div>\n";

        // download?
        echo '<hr class="c" />';
        echo "<table class='revoff'><tr>
      <td><strong>Offline reviewing</strong> &nbsp;</td>
      <td>Upload form: &nbsp; <input type='file' name='uploadedFile' accept='text/plain' size='30' />
      &nbsp; ", Ht::submit("uploadForm", "Go"), "</td>
    </tr><tr>
      <td></td>
      <td><a href='$reviewDownloadLink'>Download form</a>
      <span class='barsep'>·</span>
      <span class='hint'><strong>Tip:</strong> Use <a href='", hoturl("search"), "'>Search</a> or <a href='", hoturl("offline"), "'>Offline reviewing</a> to download or upload many forms at once.</span></td>
    </tr></table></div>\n";

        // ready?
        $ready = ($useRequest ? defval($_REQUEST, "ready") : !($rrow && $rrow->reviewModified > 1 && !$rrow->reviewSubmitted));

        // review card
        echo '<div class="revcard_body">';

        // administrator?
        $admin = $Me->allow_administer($prow);
        if ($rrow && !$Me->is_my_review($rrow) && $admin)
            echo Ht::xmsg("info", "This isn’t your review, but as an administrator you can still make changes.");

        // delegate?
        if ($rrow && !$rrow->reviewSubmitted
            && $rrow->contactId == $Me->contactId
            && $rrow->reviewType == REVIEW_SECONDARY) {
            $ndelegated = 0;
            foreach ($rrows as $rr)
                if ($rr->reviewType == REVIEW_EXTERNAL
                    && $rr->requestedBy == $rrow->contactId)
                    $ndelegated++;

            if ($ndelegated == 0)
                $t = "As a secondary reviewer, you can <a href=\"" . hoturl("assign", "p=$rrow->paperId") . "\">delegate this review to an external reviewer</a>, but if your external reviewer declines to review the paper, you should complete this review yourself.";
            else if ($rrow->reviewNeedsSubmit == 0)
                $t = "A delegated external reviewer has submitted their review, but you can still complete your own if you’d like.";
            else
                $t = "Your delegated external reviewer has not yet submitted a review.  If they do not, you should complete this review yourself.";
            echo Ht::xmsg("info", $t);
        }

        // top save changes
        if ($Me->timeReview($prow, $rrow) || $admin) {
            $buttons = $this->_review_buttons($prow, $rrow, "top", $reviewPostLink);
            echo Ht::actions($buttons, array("class" => "aa", "style" => "margin-top:0"));
        }

        // blind?
        if ($Conf->review_blindness() == Conf::BLIND_OPTIONAL) {
            echo '<div class="revet"><span class="revfn">',
                Ht::checkbox_h("blind", 1, ($useRequest ? defval($_REQUEST, 'blind') : (!$rrow || $rrow->reviewBlind))),
                "&nbsp;", Ht::label("Anonymous review"),
                "</span><hr class=\"c\" /></div>\n",
                '<div class="revhint">', htmlspecialchars(Conf::$gShortName), " allows either anonymous or open review.  Check this box to submit your review anonymously (the authors won’t know who wrote the review).</div>\n",
                '<div class="g"></div>', "\n";
        }

        // form body
        $this->webFormRows($Me, $prow, $rrow, $useRequest);

        // review actions
        if ($Me->timeReview($prow, $rrow) || $admin) {
            $buttons = $this->_review_buttons($prow, $rrow, "bottom", $reviewPostLink);
            echo Ht::actions($buttons, array("class" => "aa", "style" => "margin-bottom:0"));
            if ($rrow && $rrow->reviewSubmitted && !$admin)
                echo '<div class="hint">Only administrators can remove or unsubmit the review at this point.</div>';
        }

        echo "</div></div></div></form>\n\n";
        Ht::stash_script('hiliter_children(".editrevform")', "form_revcard");
    }

    function unparse_review_json($prow, $rrow, $contact, $include_displayed_at = false) {
        global $Conf;
        self::check_review_author_seen($prow, $rrow, $contact);
        $rj = array("pid" => $prow->paperId, "rid" => $rrow->reviewId);
        if ($rrow->reviewOrdinal)
            $rj["ordinal"] = unparseReviewOrdinal($rrow->reviewOrdinal);
        if ($contact->can_view_review_round($prow, $rrow, null)) {
            $rj["rtype"] = (int) $rrow->reviewType;
            if (($round = $Conf->round_name($rrow->reviewRound)))
                $rj["round"] = $round;
        }
        if ($rrow->reviewBlind)
            $rj["blind"] = true;
        if ($rrow->reviewSubmitted)
            $rj["submitted"] = true;
        else if (!$rrow->reviewOrdinal)
            $rj["draft"] = true;
        if ($contact->can_review($prow, $rrow))
            $rj["editable"] = true;

        // identity and time
        $showtoken = $contact->review_token_cid($prow, $rrow);
        if ($contact->can_view_review_identity($prow, $rrow, null)
            && (!$showtoken || !Contact::is_anonymous_email($rrow->email))) {
            $rj["author"] = Text::user_html($rrow);
            $rj["author_email"] = $rrow->email;
        }
        if ($showtoken)
            $rj["author_token"] = encode_token((int) $rrow->reviewToken);
        if ($rrow->reviewModified > 1 && $contact->can_view_review_time($prow, $rrow)) {
            $rj["modified_at"] = (int) $rrow->reviewModified;
            $rj["modified_at_text"] = $Conf->printableTime($rrow->reviewModified);
        }
        if ($include_displayed_at)
            // XXX exposes information, should hide before export
            $rj["displayed_at"] = (int) $rrow->timeDisplayed;

        // ratings
        if ($contact->can_view_review_ratings($prow, $rrow)) {
            if ($rrow->canViewRatings)
                $rj["ratings"] = json_decode("[" . $rrow->allRatings . "]");
            if ($contact->can_rate_review($prow, $rrow))
                $rj["user_rating"] = $rrow->myRating === null ? null : (int) $rrow->myRating;
        }

        // review text
        // (field UIDs always are uppercase so can't conflict)
        $revViewScore = $contact->view_score_bound($prow, $rrow);
        foreach ($this->forder as $fid => $f)
            if ($f->view_score > $revViewScore
                && (!$f->round_mask || $f->is_round_visible($rrow))) {
                if ($f->has_options)
                    $rj[$f->uid] = $f->unparse_value($rrow->$fid);
                else
                    $rj[$f->uid] = $rrow->$fid;
            }
        if (($fmt = $rrow->reviewFormat) === null)
            $fmt = Conf::$gDefaultFormat;
        if ($fmt)
            $rj["format"] = $fmt;

        return (object) $rj;
    }


    function reviewFlowEntry($contact, $rrow, $trclass) {
        // See also CommentInfo::unparse_flow_entry
        global $Conf;
        $barsep = " <span class='barsep'>·</span> ";
        $a = "<a href='" . hoturl("paper", "p=$rrow->paperId#r" . unparseReviewOrdinal($rrow)) . "'";
        $t = "<tr class='$trclass'><td class='pl_activityicon'>" . $a . ">"
            . Ht::img("review24.png", "[Review]", "dlimg")
            . "</a></td><td class='pl_activityid pnum'>"
            . $a . ">#$rrow->paperId</a></td><td class='pl_activitymain'><small>"
            . $a . " class=\"ptitle\">" . htmlspecialchars($rrow->shortTitle);
        if (strlen($rrow->shortTitle) != strlen($rrow->title))
            $t .= "...";
        $t .= "</a>";
        if ($rrow->reviewModified > 1) {
            if ($contact->can_view_review_time($rrow, $rrow))
                $time = $Conf->parseableTime($rrow->reviewModified, false);
            else
                $time = $Conf->unparse_time_obscure($Conf->obscure_time($rrow->reviewModified));
            $t .= $barsep . $time;
        }
        if ($contact->can_view_review_identity($rrow, $rrow, false))
            $t .= $barsep . "<span class='hint'>review by</span> " . Text::user_html($rrow->reviewFirstName, $rrow->reviewLastName, $rrow->reviewEmail);
        $t .= "</small><br /><a class='q'" . substr($a, 3) . ">";

        $revViewScore = $contact->view_score_bound($rrow, $rrow);
        if ($rrow->reviewSubmitted) {
            $t .= "Review #" . unparseReviewOrdinal($rrow) . " submitted";
            $xbarsep = $barsep;
        } else
            $xbarsep = "";
        foreach ($this->forder as $field => $f)
            if ($f->view_score > $revViewScore && $f->has_options
                && $rrow->$field) {
                $t .= $xbarsep . $f->name_html . "&nbsp;"
                    . $f->unparse_value($rrow->$field, ReviewField::VALUE_SC);
                $xbarsep = $barsep;
            }

        return $t . "</a></td></tr>";
    }
}
