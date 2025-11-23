<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Loader;
use Otus\Dealerservice\Orm\AutoTable;

class CBPAutoActivity extends BaseActivity
{
    /**
     * @see parent::_construct()
     * @param $name string Activity name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Make' => '',
            'Model' => '',
            'Number' => '',
            'Year' => '',
            'Color' => '',
            'Mileage' => '',
            'ResponsibleId' => '',
            'ClientId' => '',
            'AutoId' => null,
        ];

        $this->SetPropertiesTypes([
            'ClientId' => ['Type' => FieldType::INT],
            'ResponsibleId' => ['Type' => FieldType::INT],
            'Year' => ['Type' => FieldType::INT],
            'Mileage' => ['Type' => FieldType::INT],
            'AutoId' => ['Type' => FieldType::INT],
        ]);
    }

    /**
     * Return activity file path
     * @return string
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * @return ErrorCollection
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();
        
        if (!$errors->isEmpty()) 
        {
            return $errors;
        }

        $auto = AutoTable::getList([
            'filter' => ['NUMBER' => $this->Number],
            'select' => ['ID']
        ])->fetch();
        
        if ($auto && $auto['ID']) 
        {
            $this->AutoId = $auto['ID'];
            $this->writeToBizprocLog("Найден существующий автомобиль с ID: " . $this->AutoId);
        } else 
        {
            try 
            {
                $result = AutoTable::add([
                    'MAKE' => $this->Make,
                    'MODEL' => $this->Model,
                    'NUMBER' => $this->Number,
                    'YEAR' => $this->Year,
                    'COLOR' => $this->Color,
                    'MILEAGE' => $this->Mileage,
                    'STATUS' => AutoTable::NEW,
                    'CLIENT_ID' => $this->ClientId,
                    'CREATED_BY_ID' => $this->ResponsibleId,
                    'UPDATED_BY_ID' => $this->ResponsibleId,
                ]);
                
                if ($result->isSuccess()) {
                    $this->AutoId = $result->getId();
                    $this->writeToBizprocLog("Создан новый автомобиль с ID: " . $this->AutoId);
                } else {
                    $errorMessages = $result->getErrorMessages();
                    foreach ($errorMessages as $error) {
                        $errors->setError(new \Bitrix\Main\Error($error));
                    }
                    $this->writeToBizprocLog("Ошибка при создании автомобиля: " . implode(', ', $errorMessages));
                }
            } catch (\Exception $e) {
                $errors->setError(new \Bitrix\Main\Error($e->getMessage()));
                $this->writeToBizprocLog("Исключение при создании автомобиля: " . $e->getMessage());
            }
        }
        return $errors;
    }

   
    private function writeToBizprocLog(string $message): void
    {
        if (method_exists($this, 'log')) {
            $this->log($message);
        }
    }

    /**
     * @param PropertiesDialog|null $dialog
     * @return array[]
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        $map = [
            'Make' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_MAKE_FIELD'),
                'FieldName' => 'make',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'Model' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_MODEL_FIELD'),
                'FieldName' => 'model',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'Year' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_YEAR_FIELD'),
                'FieldName' => 'year',
                'Type' => FieldType::INT,
                'Required' => true,
            ],
            'Number' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_NUMBER_FIELD'),
                'FieldName' => 'number',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'Color' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_COLOR_FIELD'),
                'FieldName' => 'color',
                'Type' => FieldType::STRING,
                'Required' => false,
            ],
            'ResponsibleId' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_RESPONSIBLE_FIELD'),
                'FieldName' => 'responsibleId',
                'Type' => FieldType::INT,
                'Required' => true,  
            ],
            'ClientId' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_CLIENT_FIELD'),
                'FieldName' => 'clientId',
                'Type' => FieldType::INT,
                'Required' => true,  
            ],
            'Mileage' => [
                'Name' => Loc::GetMessage('BPAA_TITLE_MILEAGE_FIELD'),
                'FieldName' => 'mileage',
                'Type' => FieldType::INT,
                'Required' => false,
            ],
        ];
        return $map;
    }
}