<?php namespace Vis\ImageStorage;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class Document extends AbstractImageStorageFile
{
    protected $table = 'vis_documents';
    protected $configPrefix = 'document';

    public function getSource($size = 'source')
    {
        //temp solution for getting default field with App::getLocale
        if ($size == Config::get('translations.config.def_locale')) {
            $size = 'source';
        }

        $field = $this->sizePrefix . $size;
        $source = $this->file_folder . $this->$field;

        return $source;
    }

    public function tags()
    {
        return $this->morphToMany('Vis\ImageStorage\Tag', 'entity', 'vis_tags2entities', 'id_entity', 'id_tag');
    }

    public function afterSaveAction()
    {
        $this->makeRelations();
    }

    public function getRelatedEntities()
    {
        $relatedEntities = [];

        $relatedEntities['sizes'] = $this->getConfigSizes();

        $relatedEntities['tag'] = Tag::active()->byId()->get();

        return $relatedEntities;
    }

    private function makeRelations()
    {
        $this->makeImageTagsRelations();
    }

    private function makeImageTagsRelations()
    {

        $tags = Input::get('relations.image-storage-tags', array());

        $this->tags()->sync($tags);

        self::flushCache();
        Tag::flushCache();
    }

    private function doMakeFile($size = 'source')
    {
        $field = $this->sizePrefix . $size;

        if ($this->sourceFile) {
            $this->extension = $this->sourceFile->guessExtension();
        } else {
            $this->extension = $this->getFileExtension($size);
        }

        $fileName = $this->makeFileName();

        $destinationPath = $this->doMakeFoldersAndReturnPath($size);

        $this->sourceFile->move(public_path() . $destinationPath, $fileName);

        $this->$field = $size . "/" . $fileName;
    }

    private function doSizesVariations()
    {
        $checkColumns = $this->doCheckSchemeSizes();

        if (count($checkColumns)) {
            $this->updateWithNewSize($checkColumns);
        }

        $sourceFile = $this->sizePrefix . "source";

        $sizes = $this->getConfigSizesModifiable();
        foreach ($sizes as $size => $sizeInfo) {
            $field = $this->sizePrefix . $size;
            $this->$field = $this->$sourceFile;
        }
    }

    public function setNewFileData()
    {
        DB::beginTransaction();

        try {
            $this->setFileTitle();
            $this->save();

            $this->setFileFolder();
            $this->doMakeFile();
            $this->doSizesVariations();
            $this->save();

            DB::commit();
            return true;

        } catch (\Exception $e) {

            DB::rollBack();
            return false;
        }
    }

    public function replaceSingleFile($size)
    {
        $this->doMakeFile($size);
    }

    private function updateWithNewSize($sizes)
    {
        $entities = self::all()->except($this->id);

        $sourceFile = $this->sizePrefix . "source";

        foreach ($sizes as $key => $sizeName) {
            $field = $this->sizePrefix . $sizeName;

            foreach ($entities as $document) {
                $document->$field = $document->$sourceFile;
                $document->save();
            }
        }

    }
}
