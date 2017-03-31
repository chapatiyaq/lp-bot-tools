<?php
class LastMatchDataParser {
    private $proxy;
    private $defaultDateIsEnDate;
    private $forceEmptyDateField;
    private $forceNoDateField;
    private $noDateIfEndDate;
    private $matchesBefore3amCountForTheDayBefore;
    private $groupDatesInSlots;
    private $splitSlotsByWdl;
    private $addFourthPlace;
    private $noLastMatchDataIfNoPrize;

    private $title;
    private $originalText;
    private $modifiedText;

    private $cc;
    private $endDate;
    private $players;
    private $matches;
    private $prizePoolTableCount;
    private $playersInPrizePoolTablesCount;
    private $playersFromOutsidePrizePoolTablesCount;
    private $noPlayersFromPrizePoolTables;

    const USER_AGENT = 'Mozilla/5.0 (compatible; LastMatchDataParser/1.0; chapatiyaq@gmail.com)';
    const WIKI = 'starcraft2';

    public function __construct($title, $wikitext, $options = array()) {
        $this->setTitle($title);
        $this->setOriginalText($wikitext);
        $this->parseOptions($options);
    }

    private function parseOptions($options) {
        $this->proxy = isset($options['proxy']) ? $options['proxy'] : false;
        $this->defaultDateIsEndDate = isset($options['defaultDateIsEndDate']) ? $options['defaultDateIsEndDate'] : false;
        $this->forceEmptyDateField = isset($promptOptions['forceEmptyDateField']) ? $options['forceEmptyDateField'] : false;
        $this->forceNoDateField = isset($promptOptions['forceNoDateField']) ? $options['forceNoDateField'] : true;
        $this->noDateIfEndDate = isset($options['noDateIfEndDate']) ? $options['noDateIfEndDate'] : true;
        $this->matchesBefore3amCountForTheDayBefore = isset($options['matchesBefore3amCountForTheDayBefore']) ? $options['matchesBefore3amCountForTheDayBefore'] : true;
        $this->groupDatesInSlots = isset($options['groupDatesInSlots']) ? $options['groupDatesInSlots'] : true;
        $this->splitSlotsByWdl = isset($options['splitSlotsByWdl']) ? $options['splitSlotsByWdl'] : false;
        $this->addFourthPlace = isset($options['addFourthPlace']) ? $options['addFourthPlace'] : false;
        $this->noLastMatchDataIfNoPrize = isset($options['noLastMatchDataIfNoPrize']) ? $options['noLastMatchDataIfNoPrize'] : true;
    }

    private function setTitle($title) {
        $this->title = $title;
    }

    private function setOriginalText($wikitext) {
        $this->originalText = $wikitext;
        $this->modifiedText = $wikitext;
    }

    private function initCurlParams() {
        $this->cc = array();
        $this->cc['options'] = array(
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => '',
            CURLOPT_POST => true,
            //CURLOPT_USERPWD => "user:password",
            //CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_URL => 'http://wiki.teamliquid.net/' . self::WIKI .'/api.php'
        );
        if (isset($this->proxy))
            $this->cc['options'][CURLOPT_PROXY] = $this->proxy;
    }

    public function execute() {
        $this->players = array();
        $this->matches = array();
        $this->prizePoolTableCount = 0;
        $this->playersInPrizePoolTablesCount = 0;
        $this->playersFromOutsidePrizePoolTablesCount = array();
        $this->noPlayersFromPrizePoolTables = true;

        $this->initCurlParams();
        $this->executeMain();
        $this->executeGroupDatesInSlots();
        $this->executeSplitSlotsByWdl();
        $this->executeAddFourthPlace();

        return $this->modifiedText;
    }

    private function executeMain() {
        echo "Parsing $this->title...\n";
        $this->parsePage($this->title, TRUE);
        echo $this->prizePoolTableCount . " prize pool table(s) found on this page.\n";
        $promptOptions = array();
        if (!$this->noPlayersFromPrizePoolTables && empty($this->matches)) {
            echo "** No match data found in this page! **\n";
            
            if ($this->prizePoolTableCount > 1) {
                $promptOptions[] = array('multiplePrizePoolMultiplePages', 'Enter pages for each of the prize pool table');
                if ($this->prizePoolTableCount == 2) {
                    $promptOptions[] = array('gsl', 'This is a GSL Code S/Code A page');
                    $promptOptions[] = array('singlePage', 'Enter a page with results for each of the prize pool tables');
                }
            } else {
                $promptOptions[] = array('gomtvopen', 'This is a GOMTV Open Season');
                $promptOptions[] = array('singlePrizePoolMultiplePages', 'Enter multiple pages for the prize pool table');
                $promptOptions[] = array('singlePage', 'Enter a page with results for the prize pool table');
            }
            $promptOptions[] = array('doNothing', 'Do nothing');
        } else {
            $playerWithLastMatchDataCount = 0;
            for ($pptIndex = 0; $pptIndex < count($this->players); ++$pptIndex) {
                for ($j = 0; $j < count($this->players[$pptIndex]); ++$j) {
                    $playerWithLastMatchDataCount += isset($this->players[$pptIndex][$j]['lastMatch']);
                }
            }
            if ($this->playersInPrizePoolTablesCount > $playerWithLastMatchDataCount) {
                echo "** More players found in prize pool table (" . $this->playersInPrizePoolTablesCount .") than players with last match data found (" . $playerWithLastMatchDataCount .") in this page! **\n";
                $promptOptions[] = array('singlePrizePoolMultiplePages', 'Enter multiple pages for the prize pool table');
                $promptOptions[] = array('singlePage', 'Enter a page with results for the prize pool table');
                $promptOptions[] = array('doNothing', 'Do nothing');
            }
        }

        if (!empty($promptOptions)) {
            foreach ($promptOptions as $i => $option) {
                echo $i . ": " . $option[1] . "\n";
            }
            $line = trim(fgets(STDIN));

            if (isset($promptOptions[intval($line)])) {
                $option = $promptOptions[intval($line)][0];
                echo "Option: $option \n";
                if ($option == 'gsl') {
                    parsePage($this->title . '/Code S', FALSE, 0);
                    parsePage($this->title . '/Code A', FALSE, 1);
                    for ($pptIndex = 0; $pptIndex < count($this->players); ++$pptIndex) {
                        if ($this->playersFromOutsidePrizePoolTablesCount[$pptIndex] > 0)
                        {
                            echo "** " . $this->playersFromOutsidePrizePoolTablesCount[$pptIndex] ." found outside the prize pool table " . ($pptIndex + 1) . " **\n";
                            for ($i = count($this->players[$pptIndex]) - $this->playersFromOutsidePrizePoolTablesCount[$pptIndex]; $i < count($this->players[$pptIndex]); ++$i) {
                                echo $this->players[$pptIndex][$i]['name'] . ": ";
                                $line = trim(fgets(STDIN));
                                if ($line != '')
                                    $this->players[$pptIndex][$i]['name'] = $line;
                            }
                        }
                    }
                }
                if ($option == 'gomtvopen') {
                    parsePage($this->title . '/Group A', FALSE, 0);
                    parsePage($this->title . '/Group B', FALSE, 0);
                    parsePage($this->title . '/Group C', FALSE, 0);
                    parsePage($this->title . '/Group D', FALSE, 0);
                    parsePage($this->title . '/Final8', FALSE, 0);
                    if ($this->playersFromOutsidePrizePoolTablesCount[0] > 0)
                    {
                        echo "** " . $this->playersFromOutsidePrizePoolTablesCount[0] ." found outside the prize pool table **\n";
                        for ($i = count($this->players[0]) - $this->playersFromOutsidePrizePoolTablesCount[0]; $i < count($this->players[0]); ++$i) {
                            echo $this->players[0][$i]['name'] . ": ";
                            $line = trim(fgets(STDIN));
                            if ($line != '')
                                $this->players[0][$i]['name'] = $line;
                        }
                    }
                } else if ($option == 'multiplePrizePoolMultiplePages') {
                    echo "Please enter the titles of pages with results, separated with ',': ";
                    $line = trim(fgets(STDIN));
                    $pages = explode(',', $line, $this->prizePoolTableCount);
                    foreach($pages as $i => $page) {
                        parsePage(trim($page), FALSE, $i);
                    };
                } else if ($option == 'singlePrizePoolMultiplePages') {
                    echo "Please enter the titles of pages with results, separated with ',': ";
                    $line = trim(fgets(STDIN));
                    $pages = explode(',', $line);
                    foreach($pages as $i => $page) {
                        parsePage(trim($page));
                    };
                } else if ($option == 'singlePage') {
                    echo "Please enter the title of page with results: ";
                    $line = trim(fgets(STDIN));
                    parsePage($line);
                } else if ($option == 'doNothing') {
                    // Nothing
                }
            }
        }

        //echo str_replace(array("\n", "  "), "", print_r($this->players, true));
        preg_match_all("/(?s)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]start *.*?\}\}(.+?)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]end *\}\}/", $this->modifiedText, $pptMatches, PREG_SET_ORDER);

        $allEntries = array();
        for ($pptIndex = 0; $pptIndex < count($this->players); ++$pptIndex) {
            $entries = array();
            $hasAnEntryWithNonEmptyDate = FALSE;

            for ($j = 0; $j < count($this->players[$pptIndex]); ++$j) {
                $entry = array();
                $entry['player'] = array(
                    'name' => $this->players[$pptIndex][$j]['name'],
                    'page' => (isset($this->players[$pptIndex][$j]['page']) ? $this->players[$pptIndex][$j]['page'] : ''),
                    'flag' => $this->players[$pptIndex][$j]['flag'],
                    'race' => $this->players[$pptIndex][$j]['race'],
                    'team' => (isset($this->players[$pptIndex][$j]['team']) ? $this->players[$pptIndex][$j]['team'] : ''),
                );
                $entry['hasLastMatchData'] = isset($this->players[$pptIndex][$j]['lastMatch']);
                if ($entry['hasLastMatchData']) {
                    $entry['hasLastMatchInGroupStage'] = $this->players[$pptIndex][$j]['lastMatch']['inGroupStage'];
                    if ( $entry['hasLastMatchInGroupStage'] ) {
                        $entry['wdl'] = $this->players[$pptIndex][$j]['wdl'];
                    } else {
                        $vsPage = '';
                        $vsName = '';
                        $vsRace = '';
                        $vsIndex = -1;
                        if ( isset($this->players[$pptIndex][$j]['lastMatch']['vsIndex']) && $this->players[$pptIndex][$j]['lastMatch']['vsIndex'] >= 0 ) {
                            $vsIndex = $this->players[$pptIndex][$j]['lastMatch']['vsIndex'];
                            $vsPage = (isset($this->players[$pptIndex][$vsIndex]['page']) ? $this->players[$pptIndex][$vsIndex]['page'] : '' );
                            $vsName = $this->players[$pptIndex][$vsIndex]['name'];
                            $vsRace = $this->players[$pptIndex][$vsIndex]['race'];
                        } else {
                            $vsName = $this->players[$pptIndex][$j]['lastMatch']['vsName'];
                            $vsRace = $this->players[$pptIndex][$j]['lastMatch']['vsRace'];
                        }
                        $entry['legacy'] = array(
                            'vsPage' => $vsPage, 'vsName' => $vsName, 'vsRace' => $vsRace,
                            'vsIndex' => $vsIndex, 'index' => $this->players[$pptIndex][$j]['lastMatch']['index']
                        );
                        $entry['lastvs'] = ( $vsPage ? $vsPage . '{{!}}' : '' ) . $vsName;
                        $entry['lastvsrace'] = $vsRace;
                        if ( $this->players[$pptIndex][$j]['lastMatch']['score'] == 'W' ) {
                            $entry['wofrom'] = true;
                        } else if ( $this->players[$pptIndex][$j]['lastMatch']['vsScore'] == 'W' ) {
                            $entry['woto'] = true;
                        } else {
                            $entry['lastscore'] = $this->players[$pptIndex][$j]['lastMatch']['score'];
                            $entry['lastvsscore'] = $this->players[$pptIndex][$j]['lastMatch']['vsScore'];
                        }
                    }
                    if ( $this->players[$pptIndex][$j]['lastMatch']['date'] )
                        $entry['date'] = $this->players[$pptIndex][$j]['lastMatch']['date'];
                    else
                        $entry['date'] = $this->defaultDateIsEndDate && $this->endDate ? date('Y-m-d', $this->endDate) : '';
                    $hasAnEntryWithNonEmptyDate |= ($entry['date'] !== '');
                    $entries[] = $entry;
                }
            }

            //echo '<pre>' . print_r($entries, true) . '</pre>';
            //echo str_replace(array("\n", "  "), "", print_r($entries, true));

            if (isset($pptMatches[$pptIndex][1])) {
                $originalPptText = $pptMatches[$pptIndex][1];
                $modifiedPptText = $originalPptText;
                for ($j = 0; $j < count($entries); ++$j) {
                    $regExPattern = '#(?s)(\{\{ *(?:Template:)?[Pp]rize[ _]pool[ _]slot([^\n]*)(?:(?!\}\}).)*?(\| *';
                    if ($entries[$j]['player']['page'] !== '')
                    {
                        $rePage = preg_quote($entries[$j]['player']['page']);
                        $pageChar1 = strtoupper(substr($rePage, 0, 1));
                        $rePage = "[$pageChar1" . strtolower($pageChar1) . "]" . substr($rePage, 1);
                        $rePage = preg_replace('/[ _]/', '[ _]', $rePage);
                        $regExPattern .= $rePage . ' *\{\{!\}\} *';
                    }
                    $regExPattern .= $entries[$j]['player']['name'];
                    $regExPattern .= ' *\|[^\}\n]+?([0-9]+)=[^\}\n]+?)) *(?=[\}\n])#';
                    if (preg_match($regExPattern, $modifiedPptText, $matches) == 1 &&
                        strpos($matches[3], 'lastvs') === FALSE &&
                        strpos($matches[3], 'wdl') === FALSE &&
                        !($this->noLastMatchDataIfNoPrize && preg_match("/\| *usdprize *= *0 *[\n\|]/", $matches[2]) == 1) ) {
                        $pos = $matches[4];
                        $separator = (strpos($matches[3], ' |') !== FALSE) ? ' ' : '';
                        $appendText = $this->makeAppendText($pos, $entries[$j], $hasAnEntryWithNonEmptyDate, $separator);
                        $modifiedPptText = preg_replace($regExPattern, $matches[1] . $appendText, $modifiedPptText, 1);
                    }
                }
                // Ugly hack :((((
                $this->modifiedText = str_replace($originalPptText, $modifiedPptText, $this->modifiedText);
            }

            $allEntries[$pptIndex] = $entries;
        }
    }

    private function xqHasClass($class) {
        return "contains(concat(' ',normalize-space(@class),' '),' $class ')";
    }

    private function xeHasClass($class) {
        return "boolean(contains(concat(' ',normalize-space(./@class),' '),' $class '))";
    }

    private function parsePlayer($playerNode, $xpath) {
        $player = array('flag' => '', 'race' => '', 'name' => '');
        $q1es = $xpath->query('.//img/@src', $playerNode);
        if ($q1es !== FALSE) {
            foreach ($q1es as $q1e) {
                preg_match('/[^\/]*\.(png|gif)$/', $q1e->textContent, $matches);
                $fileName = $matches[0];
                if (preg_match('/^(P|T|Z|R)icon_small\.png$/', $fileName, $matches) == 1) {
                    $player['race'] = strtolower($matches[1]);
                } else if (preg_match('/^([A-Z][a-z][a-z]?)\.(png|gif)$/', $fileName, $matches) == 1) {
                    $player['flag'] = strtolower($matches[1]);
                }
            }
        }
        if ($xpath->query('.//span[@style]', $playerNode)->length) {
            $span = $xpath->query('.//span[@style]', $playerNode)->item(0);
            $player['name'] = trim($span->textContent, " \xC2\xA0\r\n");
            $q1es = $xpath->query('a/@title', $span);
            if ($q1es !== FALSE && $q1es->length > 0) {
                $page = str_replace(' (page does not exist)', '', $q1es->item(0)->textContent);
                if (self::cleanTitle($page) != self::cleanTitle($player['name'])) {
                    $player['page'] = $page;
                }
            }
        }
        else
            $player['name'] = trim($playerNode->textContent, " \xC2\xA0\r\n");
        return $player;
    }

    private function parsePlayerInPrizePoolTable($playerNode, $xpath) {
        if (is_null($playerNode))
            return;
        $player = array('flag' => '', 'race' => '', 'name' => '');
        $q1es = $xpath->query('.//img/@src', $playerNode);
        if ($q1es !== FALSE) {
            foreach ($q1es as $q1e) {
                preg_match('/[^\/]*\.(png|gif)$/', $q1e->textContent, $matches);
                $fileName = $matches[0];
                if (preg_match('/^(P|T|Z|R)icon_small\.png$/', $fileName, $matches) == 1) {
                    $player['race'] = strtolower($matches[1]);
                } else if (preg_match('/^([A-Z][a-z][a-z]?)\.(png|gif)$/', $fileName, $matches) == 1) {
                    $player['flag'] = strtolower($matches[1]);
                }
            }
        }
        $player['name'] = trim($playerNode->textContent, " \xC2\xA0\r\n");
        $q1es = $xpath->query('.//a[position()=3]/@title', $playerNode);
        if ($q1es !== FALSE && $q1es->length > 0) {
            $page = str_replace(' (page does not exist)', '', $q1es->item(0)->textContent);
            if (self::cleanTitle($page) != self::cleanTitle($player['name'])) {
                $player['page'] = $page;
            }
        }
        return $player;
    }

    private function parsePlayerInBracket($playerNode, $xpath) {
        $player = array('flag' => '', 'race' => '', 'name' => '');
        $q1es = $xpath->query('.//img/@src', $playerNode);
        if ($q1es !== FALSE) {
            foreach ($q1es as $q1e) {
                preg_match('/[^\/]*\.(png|gif)$/', $q1e->textContent, $matches);
                $fileName = $matches[0];
                if (preg_match('/^([A-Z][a-z][a-z]?)\.(png|gif)$/', $fileName, $matches) == 1) {
                    $player['flag'] = strtolower($matches[1]);
                }
            }
        }
        if ($xpath->evaluate("boolean(contains(./@style,'background:rgb(242,242,184);'))", $playerNode)) {
            $player['race'] = 'r';
        } else if ($xpath->evaluate("boolean(contains(./@style,'background:rgb(242,184,184);'))", $playerNode)) {
            $player['race'] = 'z';
        } else if ($xpath->evaluate("boolean(contains(./@style,'background:rgb(184,242,184);'))", $playerNode)) {
            $player['race'] = 'p';
        } else if ($xpath->evaluate("boolean(contains(./@style,'background:rgb(184,184,242);'))", $playerNode)) {
            $player['race'] = 't';
        }
        $player['name'] = trim($xpath->query('.//span', $playerNode)->item(0)->textContent, " \xC2\xA0\n");
        return $player;
    }

    private function cleanTitle($string) {
        return str_replace('_', ' ', ucfirst($string));
    }

    private function parseDate($dateNode) {
        $cleanDate = preg_replace("/\[[^\]]+\]/", '', $dateNode->textContent);
        $cleanDate = str_replace(' - ', ' ', $cleanDate);
        $cleanDate = preg_replace("/([0-9]{1,2}:[0-9]{2}) *[A-Z]+:?/", '$1', $cleanDate);
        echo 'cleanDate: ' . $cleanDate;
        if (preg_match("/(?:^| )(?:19|[2-9][0-9])[0-9]{2}(?:$| )/", $cleanDate, $dateMatch) == 1) {
            $dateTimestamp = strtotime($cleanDate);
            $date = getdate($dateTimestamp);
            if (($date['hours'] > 0 || $date['minutes'] > 0) && $date['hours'] < 3 && $this->matchesBefore3amCountForTheDayBefore)
                $dateTimestamp -= 3 * 3600;
            echo ' -> ' . date('Y-m-d', $dateTimestamp) . "\n";
            return date('Y-m-d', $dateTimestamp);
        }
        echo " Not parsed\n";
        return '';
    }

    private function makePlayerText($pos, $entry, $separator) {
        $bits = array();
        $bits[] = '|' . ($entry['player']['page'] != '' ? $entry['player']['page'] . '{{!}}' : '') . $entry['player']['name'];
        $bits[] = '|flag' . $pos . '=' . $entry['player']['flag'];
        $bits[] = '|race' . $pos . '=' . $entry['player']['race'];
        $bits[] = '|team' . $pos . '=' . $entry['player']['team'];
        return implode($separator, $bits);
    }

    private function makeAppendText($pos, $entry, $dateEnable, $separator) {
        $bits = array();
        if ($entry['hasLastMatchInGroupStage']) {
            $bits[] = '|wdl' . $pos . '=' . $entry['wdl'];
        } else {
            $bits[] = '|lastvs' . $pos . '=' . $entry['lastvs'];
            $bits[] = '|lastvsrace' . $pos . '=' . $entry['lastvsrace'];
            if (isset($entry['wofrom']) && $entry['wofrom']) {
                $bits[] = '|wofrom' . $pos . '=true';
            } else if (isset($entry['woto']) && $entry['woto'])  {
                $bits[] = '|woto' . $pos . '=true';
            } else {
                $bits[] = '|lastscore' . $pos . '=' . $entry['lastscore'];
                $bits[] = '|lastvsscore' . $pos . '=' . $entry['lastvsscore'];
            }
        }
        if (!$this->forceNoDateField) {
            if ($this->forceEmptyDateField) {
                $bits[] = '|date' . $pos . '=';
            } else if (($entry['date'] === '' || !($this->endDate && $entry['date'] == date('Y-m-d', $this->endDate) && $this->noDateIfEndDate)) &&
                $dateEnable) {
                $bits[] = '|date' . $pos . '=' . $entry['date'];
            }
        }
        return $separator . implode($separator, $bits);
    }

    private function parsePage($pageTitle, $parseEndDateAndPrizePoolTables = FALSE, $pptIndex = 0) {
        $curl = curl_init();
        curl_setopt_array($curl, $this->cc['options']);
        $postdata = http_build_query(array(
            'action' => 'parse',
            'prop' => 'text',
            'disablepp' => TRUE,
            'disableeditsection' => TRUE,
            'page' => $pageTitle,
            'format' => 'php'
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = unserialize(curl_exec($curl));
        curl_close($curl);
        if (!$data || isset($data['error'])) {
            echo 'Error while parsing ' . $pageTitle . ': ' . $data['error']['info'] . "\n";
            sleep(3);
            return;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(TRUE);
        $doc->loadHTML($data['parse']['text']['*']);
        $xpath = new DOMXpath($doc);

        if ($parseEndDateAndPrizePoolTables) {
            $q1es = $xpath->query("//div[" . self::xqHasClass('infobox-cell-2') . " and " . self::xqHasClass('infobox-description') . "]");
            if ($q1es !== FALSE) {
                foreach ($q1es as $q1e) {
                    if ($q1e->textContent == 'Date:' || $q1e->textContent == 'End Date:') {
                        //echo "<br/>[". $q1e->nodeName. "]" . $q1e->textContent. "\n";
                        $q2es = $xpath->query("div[" . self::xqHasClass('infobox-cell-2') . " and not(" . self::xqHasClass('infobox-description') . ")]", $q1e->parentNode);
                        $this->endDate = strtotime($q2es->item(0)->textContent);
                    }
                }
            }
            if (isset($this->endDate))
                echo 'End date ISO: ' . date('Y-m-d', $this->endDate) . "\n";

            $q1es = $xpath->query("//*[" . self::xqHasClass('prizepooltable') . "]");
            $this->noPlayersFromPrizePoolTables = true;
            if ($q1es !== FALSE) {
                foreach ($q1es as $i => $q1e) {
                    $this->players[$i] = array();
                    $this->playersFromOutsidePrizePoolTablesCount[$i] = 0;
                    $q2es = $xpath->query(".//tr", $q1e);
                    if ($q2es !== FALSE) {
                        foreach ($q2es as $q2e) {
                            $q3es = $xpath->query("td[@style='height:26px;']", $q2e);
                            if ($q3es === FALSE || !$q3es->length)
                                continue;
                            $this->players[$i][] = $this->parsePlayerInPrizePoolTable($q3es->item(0), $xpath);
                        }
                        $this->playersInPrizePoolTablesCount += count($this->players[$i]);
                    }
                    ++$this->prizePoolTableCount;
                    $this->noPlayersFromPrizePoolTables &= empty($this->players[$i]);
                }
            }
        }

        $groupPlayers = array();

        $date = '';
        $q1es = $xpath->query("//p[abbr[@data-tz]]|//i[abbr[@data-tz]]|//*[" . self::xqHasClass('grouptable') . "]|//*[" . self::xqHasClass('matchlist') . "]|//*[" . self::xqHasClass('bracket') . " and not(" . self::xqHasClass('bracket-archon-mode') . ")]|//*[" . self::xqHasClass('match-summary') . "]//tr[td[" . self::xqHasClass('matchlistslot') . "]]");
        if ($q1es !== FALSE) {
            foreach ($q1es as $q1e) {
                //echo $q1e->nodeName . "\n";
                if ($xpath->evaluate('boolean(self::p[abbr[@data-tz]]|self::i[abbr[@data-tz]])', $q1e)) {
                    $date = $this->parseDate($q1e);
                } else if ($xpath->evaluate(self::xeHasClass('grouptable'), $q1e)) {
                    //echo "grouptable\n";
                    $groupPlayers = array();
                    $q2es = $xpath->query('./tr', $q1e);
                    foreach ($q2es as $q2e) {
                        $q3es = $xpath->query('.//*[' . self::xqHasClass('datetime') . ']', $q2e);
                        if ($q3es->length) {
                            $date = $this->parseDate($q3es->item(0));
                            continue;
                        }
                        $q3es = $xpath->query('.//*[@data-tz]', $q2e);
                        if ($q3es->length) {
                            $date = $this->parseDate($q3es->item(0)->parentNode);
                            continue;
                        }
                        if (preg_match("/(?:^| )(?:19|[2-9][0-9])[0-9]{2}(?:$| )/", $q2e->textContent) == 1) {
                            $date = $this->parseDate($q2e);
                            continue;
                        }
                        $q3es = $xpath->query('./*[' . self::xqHasClass('grouptableslot') . ']', $q2e);
                        if (!$q3es->length)
                            continue;
                        $player = $this->parsePlayer($q3es->item(0), $xpath);
                        $player['lastMatch'] = array(
                            'date' => $date,
                            'inGroupStage' => true
                        );
                        $player['wdl'] = trim($xpath->query('.//b', $q2e)->item(0)->textContent);
                        if ($this->noPlayersFromPrizePoolTables) {
                            $player['index'] = count($this->players[$pptIndex]);
                            $this->players[$pptIndex][] = $player;
                        } else {
                            $player['index'] = -1;
                            for ($j = 0; $j < count($this->players[$pptIndex]); ++$j) {
                                if ($player['name'] == $this->players[$pptIndex][$j]['name'] ) {
                                    $player['index'] = $j;
                                    // Prematurely setting last match info... (in case the player has no match details later)
                                    $this->players[$pptIndex][$j]['lastMatch'] = $player['lastMatch'];
                                    $this->players[$pptIndex][$j]['wdl'] = $player['wdl'];
                                }
                            }
                            // Adding a player who was not found in a prize pool table
                            if ($player['index'] == -1) {
                                $player['index'] = count($this->players[$pptIndex]);
                                $this->players[$pptIndex][] = $player;
                                ++$this->playersFromOutsidePrizePoolTablesCount[$pptIndex];
                            }
                        }
                        $groupPlayers[] = $player;
                    }
                    //echo '<pre>' . print_r($groupPlayers, true) . '</pre>';
                } else if ($xpath->evaluate(self::xeHasClass('matchlist'), $q1e)) {
                    //echo "matchlist\n";
                    $q2es = $xpath->query('./tr[not(' . self::xqHasClass('maprow') . ')]', $q1e);
                    foreach ($q2es as $q2e) {
                        $q3es = $xpath->query('./*[@colspan=4]', $q2e);
                        if ($q3es->length) {
                            $newDate = $this->parseDate($q3es->item(0));
                            $date = $newDate != '' ? $newDate : $date;
                        }
                        $q3es = $xpath->query("./*[" . self::xqHasClass('matchlistslot') . ']', $q2e);
                        if ($q3es->length !== 2)
                            continue;
                        $player1Index = -1;
                        $player2Index = -1;
                        $player1Node = $q3es->item(0);
                        $player2Node = $q3es->item(1);
                        $player1Name = trim($player1Node->textContent, " \xC2\xA0\r\n\t");
                        $player2Name = trim($player2Node->textContent, " \xC2\xA0\r\n\t");
                        $player1Win = $xpath->evaluate("boolean(contains(./@style,'font-weight:bold'))", $player1Node);
                        $q3es = $xpath->query("./*[not(" . self::xqHasClass('matchlistslot') . ')]', $q2e);
                        $player1Score = trim($q3es->item(0)->textContent);
                        $player2Score = trim($q3es->item(1)->textContent);
                        if ($player1Win && $xpath->evaluate("boolean(contains(./@style,'font-weight:bold'))", $player2Node)) {
                            echo (++$i) . "Error: $player1Name  vs $player2Name" . "\n";
                            continue;
                        } else {
                            $player2Win = !$player1Win;
                        }
                        //echo "$date  " . $player1Name . ($player1Win ? '[W]' : '') . " $player1Score - $player2Score " . ($player2Win ? '[W]' : '') . $player2Name . "\n";
                        if (count($groupPlayers)) {
                            for ($j = 0; $j < count($groupPlayers); ++$j) {
                                if ($player1Index < 0 && $player1Name == $groupPlayers[$j]['name']) {
                                    $player1Index = $groupPlayers[$j]['index'];
                                    if ($player1Index >= 0)
                                        $this->players[$pptIndex][$player1Index]['wdl'] = $groupPlayers[$j]['wdl'];
                                } else if ($player2Index < 0 && $player2Name == $groupPlayers[$j]['name']) {
                                    $player2Index = $groupPlayers[$j]['index'];
                                    if ($player2Index >= 0)
                                        $this->players[$pptIndex][$player2Index]['wdl'] = $groupPlayers[$j]['wdl'];
                                }
                            }
                        } else {
                            for ($j = 0; $j < count($this->players[$pptIndex]); ++$j ) {
                                if ( $player1Index < 0 && $player1Name == $this->players[$pptIndex][$j]['name'] ) {
                                    $player1Index = $j;
                                } else if ( $player2Index < 0 && $player2Name == $this->players[$pptIndex][$j]['name'] ) {
                                    $player2Index = $j;
                                }
                            }
                        }
                        $this->matches[] = array(
                            $player1Name, $player1Index, $player1Win, $player1Score,
                            $player2Name, $player2Index, $player2Win, $player2Score,
                            $date
                        );
                        if ($player1Index >= 0) {
                            $lastMatch = array(
                                'index' => count($this->matches) - 1,
                                'vsName' => $player2Name,
                                'score' => $player1Score,
                                'vsScore' => $player2Score,
                                'date' => $date,
                                'inGroupStage' => count($groupPlayers) ? true : false
                            );
                            if ( $player2Index >= 0 ) {
                                $lastMatch['vsIndex'] = $player2Index;
                            }
                            $this->players[$pptIndex][$player1Index]['lastMatch'] = $lastMatch;
                        }
                        if ($player2Index >= 0) {
                            $lastMatch = array(
                                'index' => count($this->matches) - 1,
                                'vsName' => $player1Name,
                                'score' => $player2Score,
                                'vsScore' => $player1Score,
                                'date' => $date,
                                'inGroupStage' => count($groupPlayers) ? true : false
                            );
                            if ( $player1Index >= 0 ) {
                                $lastMatch['vsIndex'] = $player1Index;
                            }
                            $this->players[$pptIndex][$player2Index]['lastMatch'] = $lastMatch;
                        }
                    }
                } else if ($xpath->evaluate(self::xeHasClass('bracket'), $q1e)) {
                    //echo "bracket\n";
                    $date = '';
                    $q2es = $xpath->query(".//*[" . self::xqHasClass('bracket-game') . ']', $q1e);
                    foreach ($q2es as $q2e) {
                        $player1Index = -1;
                        $player2Index = -1;
                        $q3es = $xpath->query(".//*[" . self::xqHasClass('bracket-popup-body-time') . ']', $q2e);
                        if ($q3es->length) {
                            $date = $this->parseDate($q3es->item($q3es->length - 1));
                        }
                        $player1Node = $xpath->query(".//*[" . self::xqHasClass('bracket-player-top') . ']', $q2e)->item(0);
                        $player2Node = $xpath->query(".//*[" . self::xqHasClass('bracket-player-bottom') . ']', $q2e)->item(0);
                        $player1 = $this->parsePlayerInBracket($player1Node, $xpath);
                        $player2 = $this->parsePlayerInBracket($player2Node, $xpath);
                        $player1Win = $xpath->evaluate("boolean(contains(./@style,'font-weight:bold'))", $player1Node->parentNode);
                        $player1Score = trim($xpath->query('./*[' . self::xqHasClass('bracket-score') . ']', $player1Node)->item(0)->textContent);
                        $player2Score = trim($xpath->query('./*[' . self::xqHasClass('bracket-score') . ']', $player2Node)->item(0)->textContent);
                        if ( $player1Win && $xpath->evaluate("boolean(contains(./@style,'font-weight:bold'))", $player2Node->parentNode) ) {
                            if ( $player1Score != 'Q' || $player2Score != 'Q' )
                                echo ( ++$i ) . "Error: ${player1['name']}  vs ${player2['name']}\n";
                            continue;
                        } else {
                            $player2Win = !$player1Win;
                        }
                        //echo "$date  " . $player1['name'] . ($player1Win ? '[W]' : '') . " $player1Score - $player2Score " . ($player2Win ? '[W]' : '') . $player2['name'] . "\n";
                        for ($j = 0; $j < count($this->players[$pptIndex]); ++$j) {
                            if ($player1Index < 0 && $player1['name'] == $this->players[$pptIndex][$j]['name']) {
                                $player1Index = $j;
                            } else if ($player2Index < 0 && $player2['name'] == $this->players[$pptIndex][$j]['name']) {
                                $player2Index = $j;
                            }
                        }
                        // Adding a player who was not found in a prize pool table
                        if ($player1Index == -1) {
                            $player1['index'] = count($this->players[$pptIndex]);
                            $player1Index = $player1['index'];
                            $this->players[$pptIndex][] = $player1;
                            ++$this->playersFromOutsidePrizePoolTablesCount[$pptIndex];
                        }
                        if ($player2Index == -1) {
                            $player2['index'] = count($this->players[$pptIndex]);
                            $player2Index = $player2['index'];
                            $this->players[$pptIndex][] = $player2;
                            ++$this->playersFromOutsidePrizePoolTablesCount[$pptIndex];
                        }
                        $this->matches[] = array(
                            $player1['name'], $player1Index, $player1Win, $player1Score,
                            $player2['name'], $player2Index, $player2Win, $player2Score,
                            $date
                        );
                        // Player 1 last match data
                        $lastMatch = array(
                            'index' => count($this->matches) - 1,
                            'vsName' => $player2['name'],
                            'vsRace' => $player2['race'],
                            'score' => $player1Score,
                            'vsScore' => $player2Score,
                            'date' => $date,
                            'inGroupStage' => false
                        );
                        if ( $player2Index >= 0 ) {
                            $lastMatch['vsIndex'] = $player2Index;
                        }
                        $this->players[$pptIndex][$player1Index]['lastMatch'] = $lastMatch;
                        // Player 2 last match data
                        $lastMatch = array(
                            'index' => count($this->matches) - 1,
                            'vsName' => $player1['name'],
                            'vsRace' => $player1['race'],
                            'score' => $player2Score,
                            'vsScore' => $player1Score,
                            'date' => $date,
                            'inGroupStage' => false
                        );  
                        if ( $player1Index >= 0 ) {
                            $lastMatch['vsIndex'] = $player1Index;
                        }
                        $this->players[$pptIndex][$player2Index]['lastMatch'] = $lastMatch;
                    }
                } else {
                    //echo "match-summary matchlistslot: " . $q1e->nodeName . "\n";
                    $player1Index = -1;
                    $player2Index = -1;
                    $player1Node = $q2es->item(0);
                    $player2Node = $q2es->item(1);
                    $player1Name = trim($player1Node->textContent, " \xC2\xA0\r\n\t");
                    $player2Name = trim($player2Node->textContent, " \xC2\xA0\r\n\t");
                    $player1Win = $xpath->evaluate("boolean(contains(./@style,'font-weight:bold'))", $player1Node);
                    $q2es = $xpath->query("./*[not(" . self::xqHasClass('matchlistslot') . ')]', $q1e);
                    $player1Score = trim($q2es->item(0)->textContent);
                    $player2Score = trim($q2es->item(1)->textContent);
                    if ($player1Win && $xpath->evaluate("boolean(contains(./@style,'font-weight:bold'))", $player2Node)) {
                        echo (++$i) . "Error: $player1Name  vs $player2Name" . "\n";
                        continue;
                    } else {
                        $player2Win = !$player1Win;
                    }
                    //echo "$date  " . $player1Name . ($player1Win ? '[W]' : '') . " $player1Score - $player2Score " . ($player2Win ? '[W]' : '') . $player2Name . "\n";
                    for ($j = 0; $j < count($this->players[$pptIndex]); ++$j ) {
                        if ( $player1Index < 0 && $player1Name == $this->players[$pptIndex][$j]['name'] ) {
                            $player1Index = $j;
                        } else if ( $player2Index < 0 && $player2Name == $this->players[$pptIndex][$j]['name'] ) {
                            $player2Index = $j;
                        }
                    }
                    $this->matches[] = array(
                        $player1Name, $player1Index, $player1Win, $player1Score,
                        $player2Name, $player2Index, $player2Win, $player2Score,
                        $date
                    );
                    if ($player1Index >= 0) {
                        $lastMatch = array(
                            'index' => count($this->matches) - 1,
                            'vsName' => $player2Name,
                            'score' => $player1Score,
                            'vsScore' => $player2Score,
                            'date' => $date,
                            'inGroupStage' => false
                        );
                        if ( $player2Index >= 0 ) {
                            $lastMatch['vsIndex'] = $player2Index;
                        }
                        $this->players[$pptIndex][$player1Index]['lastMatch'] = $lastMatch;
                    }
                    if ($player2Index >= 0) {
                        $lastMatch = array(
                            'index' => count($this->matches) - 1,
                            'vsName' => $player1Name,
                            'score' => $player2Score,
                            'vsScore' => $player1Score,
                            'date' => $date,
                            'inGroupStage' => false
                        );
                        if ( $player1Index >= 0 ) {
                            $lastMatch['vsIndex'] = $player1Index;
                        }
                        $this->players[$pptIndex][$player2Index]['lastMatch'] = $lastMatch;
                    }
                }
            }
        }

        if (count($this->players)) {
            $q1es = $xpath->query("//*[" . self::xqHasClass('team-template-team-part') . "]");
            if ($q1es !== FALSE) {
                foreach ($q1es as $q1e) {
                    $q2es = $xpath->query('*["' . self::xqHasClass('team-template-image') . '"]/a/@title', $q1e);
                    if (!$q2es->length)
                        continue;
                    $team = $q2es->item(0)->textContent;
                    $player = $this->parsePlayer($q1e->parentNode, $xpath);
                    for ($i = 0; $i < count($this->players); ++$i) {
                        for ($j = 0; $j < count($this->players[$i]); ++$j) {
                            if ((!isset($this->players[$i][$j]['page']) ||
                                    !isset($player['page']) ||
                                    $this->players[$i][$j]['page'] == $player['page']) &&
                                $this->players[$i][$j]['name'] == $player['name']) {
                                $this->players[$i][$j]['team'] = strtolower($team);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    private function executeGroupDatesInSlots() {
        if ($this->groupDatesInSlots) {
            $ppsRe = "/(?s)(\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]slot *(?:\|(?:place|usdprize|localprize|points)= *(?:\[\[[^\]]*\]\]|[^\|\n]*) *| )*)(\n*.*?)(?<!\!)\}\}/";
            $textToProcess = $this->modifiedText;
            preg_match_all($ppsRe, $textToProcess, $ppsMatches, PREG_SET_ORDER);
            if ($ppsMatches !== FALSE) {
                foreach ($ppsMatches as $ppsMatch) {
                    $date = null;
                    $dateRe = "/ *\| *date[0-9]+ *= *([^\|\}\n]*)/";
                    $sameDate = true;
                    $modifiedPpsMatch = $ppsMatch[0];
                    preg_match_all($dateRe, $ppsMatch[2], $dateMatches, PREG_SET_ORDER);
                    if ($dateMatches === FALSE)
                        continue;
                    foreach ($dateMatches as $dateMatch) {
                        if ($date === null) {
                            $date = $dateMatch[1];
                        } else if ($date != $dateMatch[1]) {
                            $sameDate = false;
                            break;
                        }
                    }
                    if (count($dateMatches) > 1 && $date !== '' && $sameDate) {
                        $modifiedPpsMatch = $ppsMatch[1] . '|date=' . $date . preg_replace($dateRe, '', $ppsMatch[2]) . '}}';
                        $this->modifiedText = str_replace($ppsMatch[0], $modifiedPpsMatch, $this->modifiedText);
                    }
                }
            }
        }
    }

    private function executeSplitSlotsByWdl() {
        if ($this->splitSlotsByWdl) {
            $ppsRe = "/(?s)(\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]slot *(?:\| *(?:usdprize|localprize|points)= *(?:\[\[[^\]]*\]\]|[^\|\n]*) *| )*\| *place *= *)([0-9]+)-([0-9]+)((?:\|(?:usdprize|localprize|points)= *(?:\[\[[^\]]*\]\]|[^\|\n]*) *| )*)(\n*.*?)(?<!\!)\}\}/";
            $textToProcess = $this->modifiedText;
            preg_match_all($ppsRe, $textToProcess, $ppsMatches, PREG_SET_ORDER);
            if ($ppsMatches !== FALSE) {
                foreach ($ppsMatches as $ppsMatch) {
                    if (intval($ppsMatch[2]) <= 0 || intval($ppsMatch[3]) <= 0)
                        continue;
                    $placeLeft = intval($ppsMatch[2]);
                    $placeRight = intval($ppsMatch[3]);
                    if ($placeRight - $placeLeft % 2 == 0)
                        continue;
                    $placements = array(
                        $placeLeft . '-' . floor(($placeLeft + $placeRight) / 2),
                        ceil(($placeLeft + $placeRight) / 2) . '-' . $placeRight
                    );
                    $wdls = array();
                    $wdlRe = "/ *\| *wdl([0-9]+) *= *([^\|\}\n]*)/";
                    $modifiedPpsMatch = $ppsMatch[0];
                    preg_match_all($wdlRe, $ppsMatch[5], $wdlMatches, PREG_SET_ORDER);
                    if ($wdlMatches === FALSE || count($wdlMatches) != $placeRight - $placeLeft + 1)
                        continue;
                    foreach ($wdlMatches as $i => $wdlMatch) {
                        $wdl = trim($wdlMatch[2]);
                        if (!isset($wdls[$wdl]))
                            $wdls[$wdl] = array();
                        $wdls[$wdl][] = $wdlMatch[1];
                    }
                    krsort($wdls);
                    if (count($wdls) == 2 && count($wdls[array_keys($wdls)[0]]) == count($wdls[array_keys($wdls)[1]])) {
                        $slots = array();
                        $i = 0;
                        foreach ($wdls as $wdl => $wdlLines) {
                            $j = 1;
                            $slot = $ppsMatch[1] . $placements[$i] . $ppsMatch[4] . "\n";
                            foreach ($wdlLines as $wdlLine) {
                                preg_match("/.*wdl" . $wdlLine . " *=.*\n/", $ppsMatch[5], $wdlLineMatch);
                                $slot .= preg_replace("/(?<=flag|race|team|wdl|date)[0-9]+/", $j, $wdlLineMatch[0]);
                                ++$j;
                            }
                            $slot .= "}}";
                            $slots[] = $slot;
                            ++$i;
                        }
                        $this->modifiedText = str_replace($ppsMatch[0], implode("\n", $slots), $this->modifiedText);
                    }
                }
            }
        }
    }

    private function executeAddFourthPlace() {
        $ppsRe1 = "/(?s)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]slot *\|(?:.*?\|)? *place *= *1([^0-9-].*?)(?<!\!)\}\}/";
        $ppsRe2 = "/(?s)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]slot *\|(?:.*?\|)? *place *= *2([^0-9-].*?)(?<!\!)\}\}/";
        $ppsRe3 = "/(?s)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]slot *\|(?:.*?\|)? *place *= *3([^0-9-].*?)(?<!\!)\}\}/";
        $ppsRe4 = "/(?s)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]slot *\|(?:.*?\|)? *place *= *4([^0-9-].*?)(?<!\!)\}\}/";
        if ($this->addFourthPlace) {
            preg_match_all("/(?s)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]start *.*?\}\}(.+?)\{\{ *(?:Template *:)?[Pp]rize[ _]pool[ _]end *\}\}/", $this->modifiedText, $pptMatches, PREG_SET_ORDER);
            for ($pptIndex = 0; $pptIndex < count($pptMatches); ++$pptIndex) {
                $originalPptText = $pptMatches[$pptIndex][1];
                $modifiedPptText = $originalPptText;
                if (preg_match($ppsRe1, $modifiedPptText, $pps1Matches) == 1 &&
                    preg_match($ppsRe2, $modifiedPptText, $pps2Matches) == 1 &&
                    preg_match($ppsRe3, $modifiedPptText, $pps3Matches) == 1 &&
                    preg_match($ppsRe4, $modifiedPptText) != 1) {
                    $textToProcess = $modifiedPptText;
                    if ($ppsMatches !== FALSE) {
                        $usdPrizeFound = (preg_match("/\| *usdprize *= *(.*?) *(?:\||(?<!\!)\}\})/", $pps3Matches[1], $usdprizeMatches) == 1);
                        $localprizeFound = (preg_match("/\| *localprize *= *(.*?) *(?:\||(?<!\!)\}\})/", $pps3Matches[1], $localprizeMatches) == 1);
                        $pointsFound = (preg_match("/\| *points *= *(.*?) *(?:\||(?<!\!)\}\})/", $pps3Matches[1], $pointsMatches) == 1);
                        $dateFound = (preg_match("/\| *date1? *= *(.*?) *(?:\||(?<!\!)\}\})/", $pps3Matches[1], $pointsMatches) == 1);
                        if (preg_match("/\| *([^\|\=]+?) *(?:\||(?<!\!)\}\})/", $pps1Matches[1], $player1Matches) == 1 &&
                            preg_match("/\| *([^\|\=]+?) *(?:\||(?<!\!)\}\})/", $pps2Matches[1], $player2Matches) == 1 &&
                            preg_match("/\| *([^\|\=]+?) *(?:\||(?<!\!)\}\})/", $pps3Matches[1], $player3Matches) == 1) {
                            /*$top3 = array(
                                parsePpsPlayerParam($player1Matches[1]),
                                parsePpsPlayerParam($player2Matches[1]),
                                parsePpsPlayerParam($player3Matches[1])
                            );
                            $top3Index = array(-1, -1, -1);
                            foreach ($top3 as $key => $player) {
                                for ($j = 0; $j < count($allEntries[$pptIndex]); ++$j) {
                                    if ($allEntries[$pptIndex][$j]['player']['name'] == $playerName &&
                                        $allEntries[$pptIndex][$j]['player']['page'] == $playerPage) {
                                        $top3Index[$key] = $j;
                                        break;
                                    }
                                }
                            }*/
                            $top3Index = array(0, 1, 2);
                            if ($top3Index[0] != -1 && $top3Index[1] != -1 && $top3Index[2] != -1) {
                                $matchIndex = -1;
                                $playerIndex = -1;
                                for ($j = 0; $j < count($allEntries[$pptIndex]); ++$j) {
                                    if (isset($allEntries[$pptIndex][$j]['legacy']) &&
                                        in_array($allEntries[$pptIndex][$j]['legacy']['vsIndex'], $top3Index)) {
                                        if ($allEntries[$pptIndex][$j]['legacy']['index'] > $matchIndex) {
                                            $matchIndex = $allEntries[$pptIndex][$j]['legacy']['index'];
                                            $playerIndex = $j;
                                        }
                                    }
                                }
                                if ($playerIndex != -1) {
                                    $text4 = '{{Prize pool slot|place=4';
                                    $text4 .= $usdPrizeFound ? '|usdprize=0' : '';
                                    $text4 .= $localprizeFound ? '|localprize=0' : '';
                                    $text4 .= $pointsFound ? '|points=0' : '';
                                    $text4 .= $this->makePlayerText(1, $allEntries[$pptIndex][$playerIndex], $dateFound, '');
                                    $text4 .= $this->makeAppendText(1, $allEntries[$pptIndex][$playerIndex], $dateFound, '');
                                    $text4 .= '}}';
                                    $modifiedPptText .= $text4 . "\n";
                                    // Ugly hack :((((
                                    $this->modifiedText = str_replace($originalPptText, $modifiedPptText, $this->modifiedText);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function parsePpsPlayerParam($string) {
        $explodedString = explode("{{!}}", $string, 2);
        $player = array('page' => '', 'name' => '');
        if (count($explodedString) == 1) {
            $player['name'] = $string;
        } else {
            $player['page'] = trim($explodedString[0]);
            $player['name'] = trim($explodedString[1]);
        }
        return $player;
    }
}