<?php

namespace luya\admin\commands;

use luya\admin\base\Filter;
use luya\console\Command;
use luya\helpers\FileHelper;
use Yii;
use yii\helpers\Inflector;

/**
 * Create Storage Filters
 *
 * @author Basil Suter <basil@nadar.io>
 * @since 1.0.0
 */
class FilterController extends Command
{
    public $identifier;

    public $name;

    public $chain;

    /**
     * Create a new image filter to apply on an Image Object.
     */
    public function actionIndex()
    {
        $tempName = null;
        if ($this->identifier === null) {
            $this->identifier = Inflector::variablize($this->prompt('Enter the filter identifier: (e.g. profilePicture)', ['required' => true, 'pattern' => '/^[a-zA-Z0-9]+$/i', 'error' => 'The filter identifer can only contain a-z,A-Z,0-9']));
        }

        if ($this->chain === null) {
            $select = $this->select('Select the Effect', [Filter::EFFECT_THUMBNAIL => 'Thumbnail', Filter::EFFECT_CROP => 'Crop']);

            if ($select == Filter::EFFECT_THUMBNAIL) {
                $dimension = $this->prompt('Enter thumbnail dimensions (width x height):', ['required' => true, 'default' => '600xnull']);
            } else {
                $dimension = $this->prompt('Enter crop dimensions (width x height):', ['required' => true, 'default' => '600xnull']);
            }

            if ($select == Filter::EFFECT_THUMBNAIL) {
                $namedSelect = 'self::EFFECT_THUMBNAIL';
            } else {
                $namedSelect = 'self::EFFECT_CROP';
            }

            $xp = explode("x", $dimension);
            $this->chain[$namedSelect] = ['width' => $xp[0], 'height' => $xp[1]];


            $tempName = $select == Filter::EFFECT_THUMBNAIL ? 'Thumbnail '. $dimension : 'Crop '.$dimension;
            $tempName.= ' ' . Inflector::camel2words($this->identifier);
        }

        if ($this->name === null) {
            if (!$tempName) {
                $tempName = Inflector::camel2words($this->identifier);
            }

            $this->name = ucfirst($this->prompt('Enter a self-explanatory Name:', ['required' => false, 'default' => $tempName]));
        }

        $folder = Yii::$app->basePath . DIRECTORY_SEPARATOR . 'filters';
        $className = Inflector::camelize($this->identifier) . 'Filter';
        $content = $this->generateClassView($this->identifier, $this->name, $this->chain, $className);
        $filePath = $folder . DIRECTORY_SEPARATOR . $className . '.php';

        if (FileHelper::createDirectory($folder) && FileHelper::writeFile($filePath, $content)) {
            return $this->outputSuccess('Successfully generated ' . $filePath);
        }
    }

    /**
     *
     * @param string $identifier
     * @param string $name
     * @param array $chain
     * @param string $className
     * @return string
     */
    public function generateClassView($identifier, $name, array $chain, $className)
    {
        return $this->view->render('@admin/commands/views/filter/filterClass', [
            'identifier' => $identifier,
            'name' => addslashes($name),
            'chain' => $chain,
            'className' => $className,
            'luyaText' => $this->getGeneratorText('block/create'),
        ]);
    }
}
