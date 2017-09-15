<?php
/**
 * Created by PhpStorm.
 * User: kinfante
 * Date: 9/13/2017
 * Time: 11:48 AM
 */
class Riot
{
    //URLs
    private $summonerUri = "https://na1.api.riotgames.com/lol/summoner/v3/summoners/by-name/kinfante";
    private $matchListUri_s = "https://na1.api.riotgames.com/lol/match/v3/matchlists/by-account/";
    private $matchListUri_e = "/recent";
    private $versionsUri = "https://ddragon.leagueoflegends.com/api/versions.json";
    private $matchUri = "https://na1.api.riotgames.com/lol/match/v3/matches/";
    private $championsDataPath;
    private $itemsDataPath;
    private $spellsDataPath;
    private $uriAuth;

    //Config data
    private $configsPath = "\\configs\\config.json";
    private $apiKey;
    private $summoner;
    private $accountId;
    private $numMatches;
    private $onlyRanked;

    //Data
    private $championsData;
    private $itemsData;
    private $spellsData;
    private $version;
    private $gameIds;
    public $matches;

    function __construct()
    {
        $this->gameIds = array();
        $this->matches = array();

        $this->getConfigs();
        $this->getAccountId();
        $this->getMatchData();
        $this->getStaticData();
    }

    private function getConfigs()
    {
        $jsonObj = $this->getJsonObj(ROOT_PATH.$this->configsPath);
        $this->apiKey =  $jsonObj['api-key'];
        $this->summoner = $jsonObj['summoner-name'];
        $this->numMatches = $jsonObj['num-matches'];
        $this->onlyRanked = $jsonObj['only-ranked'];

        $this->uriAuth = "?api_key=" .$this->apiKey;
    }

    private function getAccountId()
    {
       $uri = $this->summonerUri . $this->uriAuth;
       $jsonObj = $this->getJsonObj($uri);
       $this->accountId = $jsonObj['accountId'];
    }

    private function getMatchData()
    {
        $uri = $this->matchListUri_s . $this->accountId . $this->matchListUri_e . $this->uriAuth;
        $jsonObj = $this->getJsonObj($uri);
        $this->getGameIds($jsonObj);
        $this->getMatches();
    }

    private function getGameIds($jsonObj)
    {
        $acceptableQueues = [2, 14, 4, 6, 42, 400, 410, 420, 430, 440];
        $arr = $jsonObj['matches'];
        $i = 0;
        $j = 0;
        $numMatches = intval($this->numMatches);

        while($i < $numMatches && $j < 20)
        {
            $queue = $arr[$i]['queue'];
            if(in_array($queue, $acceptableQueues))
            {
                $role = $arr[$j]['role'];
                $gameId = $arr[$j]['gameId'];
                $j++;

                if($this->onlyRanked == "true")
                {
                    if($role == 'SOLO')
                    {
                        array_push($this->gameIds,$gameId);
                        $i++;
                        continue;
                    }
                }
                else
                {
                    array_push($this->gameIds,$gameId);
                    $i++;
                    continue;
                }
            }
            else
            {
                $numMatches++;
                $i++;
                $j++;
            }
        }
    }

    private function getMatches()
    {
        for($i = 0; $i < count($this->gameIds); $i++)
        {
            $uri = $this->matchUri . $this->gameIds[$i] . $this->uriAuth;
            $jsonObj = $this->getJsonObj($uri);
            array_push($this->matches, $jsonObj);
        }
    }

    private function getStaticData()
    {
        $this->version = $this->getJsonObj($this->versionsUri)[0];
        $this->championsDataPath = "http://ddragon.leagueoflegends.com/cdn/" .$this->version. "/data/en_US/champion.json";
        $this->itemsDataPath = "http://ddragon.leagueoflegends.com/cdn/" .$this->version. "/data/en_US/item.json";
        $this->spellsDataPath = "http://ddragon.leagueoflegends.com/cdn/" .$this->version. "/data/en_US/summoner.json";
        $this->championsData = $this->getJsonObj($this->championsDataPath)["data"];
        $this->itemsData = $this->getJsonObj($this->itemsDataPath)["data"];
        $this->spellsData = $this->getJsonObj($this->spellsDataPath)["data"];
    }

    private function getJsonObj($path)
    {
        $contents = file_get_contents($path);
        $jsonObj = json_decode($contents, true);
        return $jsonObj;
    }

    public function outputMatch($match)
    {
        $map = ["TOP_1", "JUNGLE_1", "MIDDLE_1", "ADC_1", "SUPPORT_1",
                "TOP_2", "JUNGLE_2", "MIDDLE_2", "ADC_2", "SUPPORT_2"];

        $playerInfo = $this->getPlayerInfo($match);

        $html = new DOMDocument();
        $html->formatOutput = true;

        //Match Container
        $matchContainerElement = $html->createElement('div');
        $matchContainerElement->setAttribute('class', 'match-container');

        //Win/Loss
        $winLossElement = $html->createElement('div');
        $winLossElement->setAttribute('class', 'win-loss');
            $winLossSpan = $html->createElement('span');
            $winLossSpan->appendChild(new DOMText($playerInfo["wl"]));
        $winLossElement->appendChild($winLossSpan);

        //Champion Info
        $championInfoElement = $html->createElement('div');
        $championInfoElement->setAttribute('class', 'champion-info');
            $champName = $html->createElement('div');
            $champName->setAttribute('class', 'champ-name');
            $cid = $playerInfo["champion"];
            $champName->appendChild(new DOMText($this->getChampionById($cid)));

            $champDSum = $html->createElement('div');
            $s1_id = $playerInfo["sum1"];
            $champDSum->appendChild(new DOMText($this->getSummonerSpell($s1_id)));

            $champFSum = $html->createElement('div');
            $s2_id = $playerInfo["sum2"];
            $champFSum->appendChild(new DOMText($this->getSummonerSpell($s2_id)));
        $championInfoElement->appendChild($champName);
        $championInfoElement->appendChild($champDSum);
        $championInfoElement->appendChild($champFSum);

        //KDA
        $kdaElement = $html->createElement('div');
        $kdaElement->setAttribute('class', 'kda-container');
            $kda = $html->createElement('div');
            $kda->setAttribute('class', 'kda');
            $kda->appendChild(
                new DOMText(
                $playerInfo["kills"] . ' / ' . $playerInfo["deaths"] . ' / ' . $playerInfo["assists"])
                );

            $kdaCalc = $html->createElement('div');
            $kdaCalc->setAttribute('class', 'calc');
            $kdaCalc->appendChild(new DOMText($this->calcKDA($playerInfo)));
        $kdaElement->appendChild($kda);
        $kdaElement->appendChild($kdaCalc);

        //Items
        $itemsElement = $html->createElement('div');
        $itemsElement->setAttribute('class', 'items');
            for($i = 0; $i < 6; $i++)
            {
                $item = $html->createElement('div');
                $item->setAttribute('class', 'item');
                $id = $playerInfo["items"][$i];
                $val = $id != 0 ? $this->itemsData[$id]["name"] : "&nbsp;";
                $item->appendChild(new DOMText($val));
                $itemsElement->appendChild($item);
            }

        //Teams
        $teamsElement = $html->createElement('div');
        $teamsElement->setAttribute('class', 'teams');
            for($j = 0; $j < 10; $j++)
            {
                $summoner = $html->createElement('div');
                $summoner->setAttribute('class','summoner');
                    $teamChampName = $html->createElement('div');
                    $teamChampName->setAttribute('class', 'champ');
                    $cid = $playerInfo["otherPlayers"][$map[$j]];
                    $teamChampName->appendChild(new DOMText($this->getChampionById($cid)));

                $summoner->appendChild($teamChampName);
                $teamsElement->appendChild($summoner);
            }

        $matchContainerElement->appendChild($winLossElement);
        $matchContainerElement->appendChild($championInfoElement);
        $matchContainerElement->appendChild($kdaElement);
        $matchContainerElement->appendChild($itemsElement);
        $matchContainerElement->appendChild($teamsElement);

        $html->appendChild($matchContainerElement);
        return html_entity_decode($html->saveHTML());
    }

    private function getPlayerInfo($match)
    {
        $participantData = $match["participantIdentities"];
        $teams = $match["teams"];
        $playerData = $match["participants"];

        for($i = 0; $i < count($participantData); $i++)
        {
            if($participantData[$i]["player"]["accountId"] == $this->accountId)
            {
                $pid = $participantData[$i]["participantId"];
                break;
            }
        }


        $info = array(
            "wl" => $this->getWinLoss($playerData, $teams, $pid),
            "champion" => $playerData[intval($pid) - 1]["championId"],
            "sum1" => $playerData[intval($pid) - 1]["spell1Id"],
            "sum2" => $playerData[intval($pid) - 1]["spell2Id"],
            "kills" => $playerData[intval($pid) - 1]["stats"]["kills"],
            "deaths" => $playerData[intval($pid) - 1]["stats"]["deaths"],
            "assists" => $playerData[intval($pid) - 1]["stats"]["assists"],
            "level" => $playerData[intval($pid) - 1]["stats"]["champLevel"],
            "items" => $this->getItems($playerData[intval($pid) - 1]),
            "otherPlayers" => $this->getOtherChamps($playerData)
        );

        return $info;
    }

    //Get the W or the L depending on the teamId
    private function getWinLoss($playerData, $teams, $pid)
    {
        $index = 0;

        if($teams[1]["win"] == "Win") { $index = 1; }
        $winningTeamId = $teams[$index]["teamId"];
        $myTeamId = $playerData[intval($pid) - 1]["teamId"];

        return ($winningTeamId == $myTeamId) ? 'W' : 'L';
    }

    private function getItems($player)
    {
        $items = array();
        for($i = 0; $i < 6; $i++)
        {
            array_push($items, $player["stats"]["item".$i]);
        }
        return $items;
    }

    private function getOtherChamps($playerData)
    {
        $champNames = array();

        //Find all lanes from team 100
        foreach($playerData as $player)
        {
            $teamId = $player["teamId"];
            $role = $player["timeline"]["role"];
            $lane = $player["timeline"]["lane"];
            $champName = $player["championId"];

            if($teamId == "100")
            {
                if($role == "DUO_CARRY")
                {
                    $champNames["ADC_1"] = $champName;
                }
                else if($role == "DUO_SUPPORT")
                {
                    $champNames["SUPPORT_1"] = $champName;
                }
                else
                {
                    $champNames[$lane."_1"] = $champName;
                }
            }
            else
            {
                if($role == "DUO_CARRY")
                {
                    $champNames["ADC_2"] = $champName;
                }
                else if($role == "DUO_SUPPORT")
                {
                    $champNames["SUPPORT_2"] = $champName;
                }
                else
                {
                    $champNames[$lane."_2"] = $champName;
                }
            }
        }

        return $champNames;
    }

    private function calcKDA($playerInfo)
    {
        $kills = intval($playerInfo["kills"]);
        $deaths = intval($playerInfo["deaths"]);
        $assists = intval($playerInfo["assists"]);

        if($deaths == 0) return "Perfect";

        $kda = ($kills + $assists) / $deaths;
        return number_format($kda, 2) . ':1 KDA';
    }

    private function getChampionById($id)
    {
        foreach($this->championsData as $champ)
        {
            if($champ["key"] == $id)
            {
                return $champ["name"];
            }
        }
    }

    private function getSummonerSpell($id)
    {
        foreach($this->spellsData as $spell)
        {
            if($spell["key"] == $id)
            {
                return $spell["name"];
            }
        }
    }
}
?>