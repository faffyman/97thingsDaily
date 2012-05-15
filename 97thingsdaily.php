<?php
/**
 * nstepsk = 97 Things Every Programemr Should Know
 * It's a class that quite simply takes 1 item from the 97 Things Every Programmer Should Know wiki
 * And emails/display's it on screen.
 *
 * It should be the same thing displayed any page view on a given day
 * so Day 1 jan = 1st Thing on list and so on
 *
 * Could be combined with a Cron Job to email said thing.
 *
 *
 * References
 *
 * The Website: http://programmer.97things.oreilly.com/wiki/index.php/97_Things_Every_Programmer_Should_Know
 *
 * The Book, By Kevlin Henney : http://amzn.to/xicw0B
 * Kindle Version of the book : http://amzn.to/wyVGPR
 * Twitter: twitter.com/KevlinHenney
 *
 *
 */

class nstepsk {

    var $aThing;
    var $nThing; // item that made the book
    var $nOtherThing; //if coming from the list of shortlisted items that didn't make the book
    var $sThingListPage = 'http://programmer.97things.oreilly.com/wiki/index.php/Contributions_Appearing_in_the_Book '; // Book list
    var $sThingOtherListPage = 'http://programmer.97things.oreilly.com/wiki/index.php/Other_Edited_Contributions'; // shortlist of items that didn't make it into the book
    var $sList = 'main';

    // placeholders for teh title, contents and URL of today's "thing"
    var $sThingTitle = '97 Things Every Programmer Should Know';
    var $sThingText;
    var $sThingPageUrl = 'http://programmer.97things.oreilly.com';


    /**
     * Simple constructor
     * automatically grabs the "thing of the day"
     */
    public function __construct()
    {
        $this->workOutThingFromDate();
        // use a little text file to track the last one displayed/sent
        $this->workOutThingFromLog();
    }



    /**
     * Check our flat textfile log to see what the last thing grabbed was,
     * then get the next one in the list, and update the logfile.
     */
    public function workOutThingFromLog()
    {
        $fLast = fopen('97last.txt','r'); // Obviously you'll need to amend this path to a writable folder.
        //Some defaults
        $nItem = 1;
        $nLastOneSent = 0;
        $sList = 'main';
        // if day of week is mon-fri we'll continue and update the text file,
        if(date('N')<=5) {
            //read out the last id sent
            $sLastOneSent  = fread($fLast,9999);
            fclose($fLast);
            if(!empty($sLastOneSent)) {
                $aLastSent = explode('|',$sLastOneSent);
                $nLastOneSent = $aLastSent[0];
                $nItem = $nLastOneSent;
                $sList = $aLastSent[1];
            }
            // uncomment to debug
            // echo 'last item sent was item '.$sLastOneSent.'<br />';

            // update the item if it's a different day
            if(empty($aLastSent) || date('Ymd') != $aLastSent[2] ) {

                // work out next item
                $nItem = $nLastOneSent + 1;
                if ($nItem>68 && $sList=='other') {
                    $nItem = 1;
                    $sList = 'main';
                } elseif ($nItem>97 && $sList=='main') {
                    $nItem = 1;
                    $sList = 'other';
                }

                $fLast = fopen('97last.txt','w');  // Obviously you'll need to amend this path to a writable folder.
                @fwrite($fLast,$nItem.'|'.$sList.'|'.date('Ymd'));
                fclose($fLast);
            }

        } else {
            die('Not available today');
        }

        //use shut up operator to close file -just in case it was left open.
        @fclose($fLast);

        $this->nThing = $nItem;
        $this->sList = $sList;

    }


    /**
     * Possibly the most important part
     * based on the day of the year - work out which "thing" to display
     *
     * There are 97 things in the main list and 68 in the "Other" list 165 in total
     *
     */
    public function workOutThingFromDate() {

        $nDay = date('z') + 1;

        $this->sList = 'main'  ;
        $nItem = ($nDay % 165);
        if($nItem == 0) {
            $nItem = 165;
        }
        if($nItem > 97) {
            $nItem = ($nItem-97) % 69 ;
            $sList = 'other';
        } else {
            $sList = 'main';
        }

        $this->sList = $sList;
        $this->nThing = $nItem ;

    }



    /**
     * Now that we know which "thing", fetch the content
     *
     * @param bool $bEcho
     * @return string
     */
    public function fetchItem($bEcho = false)
    {
        //fetching from main(in teh book) list or didn't make the book shortlist
        $sPage = $this->sList=='main' ? $this->sThingListPage : $this->sThingOtherListPage;
        $sListPageHTML = file_get_contents($sPage);
        $nPos1 = strpos($sListPageHTML,'<ol>');
        $nPos2 = strpos($sListPageHTML,'</ol>');
        $sListPageHTML = substr($sListPageHTML,$nPos1,$nPos2-$nPos1).'</ol>';

        // transform the HTML to easier to work with XML
        $oListPageDOM = new DOMDocument();
        $oListPageDOM->loadHTML($sListPageHTML);
        $oListPageDOM->saveXML();

        // Fetch the numbered link based on our class nThing property
        $oLiList = $oListPageDOM->getElementsByTagName('li');
        $oLi = $oLiList->item( ($this->nThing-1) ) ;
        $oA = $oLi->getElementsByTagName('a');
        $oA = $oA->item(0);

        $sThingTitle = $oA->nodeValue;
        $sHref = $oA->getAttribute('href');
        $this->sThingPageUrl = 'http://programmer.97things.oreilly.com'.$sHref ;

        //get Thing page contents
        if (empty($sHref)) {
            return 'Error: item not found';
        }

        // Fetch the contents HTML page
        $sItemPageHTML = file_get_contents('http://programmer.97things.oreilly.com'.$sHref);
        $sItemPageHTML = str_ireplace('<a name="top" id="top"></a>','',$sItemPageHTML);

        // Again use DomDocument to parse it.
        $oItemPageDOM = new DOMDocument();
        $oItemPageDOM->loadHTML($sItemPageHTML);

        // Remove some Dud nodes that we don't want
        $oDudNode1 = $oItemPageDOM->getElementById('contentSub');
        $oDudNode2 = $oItemPageDOM->getElementById('siteSub');
        $oDudNode3 = $oItemPageDOM->getElementById('jump-to-nav');

        $oDudNode1->parentNode->removeChild($oDudNode1);
        $oDudNode2->parentNode->removeChild($oDudNode2);
        $oDudNode3->parentNode->removeChild($oDudNode3);

        // Main content node
        $oContentNode = $oItemPageDOM->getElementById('bodyContent');
        $sContent = $oContentNode->nodeValue;

        // Set the title and content properties
        $this->sThingTitle = $sThingTitle;
        $this->sThingText = $sContent;

        // Are we in debug mode?
        if($bEcho) {
            echo '<h1>'.$sThingTitle.'</h1>';
            echo nl2br($sContent);
        }

        return true;

    }




}// end class
