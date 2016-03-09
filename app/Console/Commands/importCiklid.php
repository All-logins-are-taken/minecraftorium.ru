<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Genus;
use App\Specie;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Symfony\Component\DomCrawler\Crawler;

class importCiklid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ciklid:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import media from Ciklid.org';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $domain = 'https://www.ciklid.org';

    public function __construct()
    {
        set_time_limit(0);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //

        /*$speciesLinks = [];
        $html = file_get_contents($this->domain. '/artregister/index.php');
        $crawler = new Crawler($html);
        $crawler->filter('a[href^="art_slakte.php"]')->each(function(Crawler $link) use(&$speciesLinks){
            $this->info("parse: " . $link->text());
            $url = $this->domain . '/artregister/' . $link->attr('href');
            $this->info($url);
            $links = $this->getSpeciesLinks($url);
            $speciesLinks = array_merge($speciesLinks, $links);
        });*/

        //print_r($speciesLinks);

        //file_put_contents(storage_path('app/links.json'), json_encode($speciesLinks));


            $speciesLinks = json_decode(file_get_contents(storage_path('app/links.json')));
            foreach($speciesLinks as $url){
                $specie = $this->parseSpeciePage($url);
                if(!empty($specie->genus) && !empty($specie->name)){
                    $this->syncSpecie($specie);
                }
            }
    }


    private function syncSpecie($specieImport){
       /// $this->info("genus: " . $specieImport->genus);
        //print_r($specieImport);
        $genus = \App\Genus::where('name', 'like', $specieImport->genus)->first();
        if($genus){
            $this->info("find genus: " . $specieImport->genus);
        }else{
            $this->info("create genus: " . $specieImport->genus);
            $genus = new \App\Genus();
            $genus->name = $specieImport->genus;
            $genus->parent_id = 261;
            $genus->save();
        }

        $specie = null;
        foreach($specieImport->synonyms as $name){
            $specie = \App\Specie::where('name', 'like', $name)->where('genus_id', $genus->id)->first();
            $this->info("find specie: " .$specieImport->name);
            break;
        }

        if(empty($specie)){
            $this->info("create specie: " .$specieImport->name);
            $specie = new \App\Specie();
            $specie->name = $specieImport->name;
            $specie->genus_id = $genus->id;
        }


        $specie->synonyms = serialize($specieImport->synonyms);

        if(!empty($specieImport->image)){
            $imagePath = 'images/species/' . uniqid(str_slug($specieImport->name, "-") . "-") ."." . $ext = pathinfo(basename($specieImport->image), PATHINFO_EXTENSION);
            $dst = strtolower(public_path($imagePath));

            try{
                if(copy($specieImport->image, $dst)){
                    $specie->image = $imagePath;
                }
            }catch (\Exception $e){

            }

        }

        if(!empty($specieImport->length)) $specie->length = $specieImport->length;

        $specie->save();

        $specie_photos = \App\SpeciePhoto::where('specie_id', $specie->id)->get();
        if(!$specie_photos->count()){
            if(!empty($specieImport->photos)){
                foreach($specieImport->photos as $photo){
                    $specie_photo = new \App\SpeciePhoto();

                    $imagePath = 'images/species/' . uniqid(str_slug($specieImport->name, "-") . "-") ."." . $ext = pathinfo(basename($photo->url), PATHINFO_EXTENSION);
                    $dst = strtolower(public_path($imagePath));

                    try{
                        if(copy($photo->url, $dst)){
                            $specie_photo->path = $imagePath;
                            if(!empty($photo->author)) $specie_photo->author = $photo->author;
                            if(!empty($photo->title)) $specie_photo->author = $photo->title;
                            $specie_photo->specie_id = $specie->id;

                            $specie_photo->save();
                        }
                    }catch (\Exception $e){

                    }
                }
            }
        }

    }


    private function parseSpeciePage($url){
        $html = file_get_contents($url);
        $crawler = new Crawler($html);

        $specie = new \stdClass();
        $specie->url = $url;
        $specie->image = null;
        $specie->year = null;
        $specie->photos = [];
        $specie->length = null;

        $aImage = $crawler->filter('body > div.container-fluid > div.thumbnail > a[href^="/artregister/artbild"]');

        if($aImage->count()){
            $specie->image = $this->domain . $aImage->attr('href');
        }

        $props = $crawler->filter('body > div.container-fluid > div:nth-child(12) > div.col-md-7');
        if($props->count()){
            $propsArray = explode('<br>', $props->html());
            foreach($propsArray as $key=>$prop){
                $prop = strip_tags($prop);
                $prop = preg_replace('/\n/', ' ', $prop);
                $prop = preg_replace('/\s{1,}/', ' ', $prop);
                $prop = trim($prop);
                $propsArray[$key] = $prop;
            }
            array_pop($propsArray);

            foreach($propsArray as $key=>$prop){
                $propArray = explode(': ', $prop);
                if($propArray[0] == 'Tidigare vetenskapligt namn'){
                    $propArray[1] = explode(", ", $propArray[1]);
                }
                if(isset($propArray[1])){
                    $propsArray[$propArray[0]] = $propArray[1];
                }
                unset($propsArray[$key]);
            }

            $specie->genus = $propsArray['Släkte'];
            $specie->name = str_replace('"', "'" , $propsArray['Art']);
            $specie->synonyms[] = $specie->name;
            if(!empty($propsArray['Tidigare vetenskapligt namn'])){
                foreach($propsArray['Tidigare vetenskapligt namn'] as $p){
                    $specie->synonyms[] = str_replace('"', "'" , $p);
                }
            }

            if(!empty($propsArray['År'])) $specie->year = $propsArray['År'];
        }

        $length = $crawler->filter('body > div.container-fluid > div:nth-child(12) > div:nth-child(4)');
        if($length->count()){
            $length = $length->text();
            if(preg_match('/(\d+)\scm/', $length, $match)){
                $specie->length = $match[1];
            }
        }


        $photos = $crawler->filter('a[data-toggle="lightbox"]');
        if($photos->count()){
            $specie->photos = $photos->each(function(Crawler $a){

                $photo = (object)array(
                    'url' => null,
                    'title' => null,
                    'author' => null
                );

                $captionHtml = $a->nextAll()->html();

                if(preg_match('/\<h4\>(.*)\<\/h4\>/', $captionHtml, $match)){
                    if(!empty($match[1])) $photo->title = $match[1];
                }
                if(preg_match('/\<p\>Foto\: (.*)\<\/p\>/', $captionHtml, $match)){
                    if(!empty($match[1])) $photo->author = $match[1];
                }
                /*$title = $caption->filter('h4');
                if($title->count()) $photo->title = $title->text();

                $author = $caption->filter('h4');
                if($author->count()) $photo->author = trim(str_replace('Foto: ', '',$author->text()));*/


                $photo->url = 'https://www.ciklid.org' . $a->attr('href');

                return $photo;
            });
        }


        return $specie;
    }

    private function getSpeciesLinks($url){

        $html = file_get_contents($url);
        $crawler = new Crawler($html);

        $links = $crawler->filter('a[href^="art.php"]')->each(function(Crawler $link){
            return $this->domain . '/artregister/' . $link->attr('href');
        });

        return array_unique($links);
    }
}
