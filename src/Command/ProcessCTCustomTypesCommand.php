<?php

namespace BestIt\CTCustomTypesBundle\Command;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Type\FieldDefinition;
use Commercetools\Core\Model\Type\FieldDefinitionCollection;
use Commercetools\Core\Model\Type\Type;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeDraft;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Request\Types\Command\TypeAddFieldDefinitionAction;
use Commercetools\Core\Request\Types\Command\TypeChangeFieldDefinitionOrderAction;
use Commercetools\Core\Request\Types\Command\TypeChangeLabelAction;
use Commercetools\Core\Request\Types\Command\TypeChangeNameAction;
use Commercetools\Core\Request\Types\Command\TypeRemoveFieldDefinitionAction;
use Commercetools\Core\Request\Types\Command\TypeSetDescriptionAction;
use Commercetools\Core\Request\Types\TypeCreateRequest;
use Commercetools\Core\Request\Types\TypeDeleteByKeyRequest;
use Commercetools\Core\Request\Types\TypeQueryRequest;
use Commercetools\Core\Request\Types\TypeUpdateByKeyRequest;
use Commercetools\Core\Response\ErrorResponse;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Core\Response\ResourceResponse;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessCTCustomTypesCommand
 * @author lange <lange@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @subpackage Command
 * @todo Refactor and unittest.
 * @version $id$
 */
class ProcessCTCustomTypesCommand extends ContainerAwareCommand
{
    /**
     * Maps checks and action creation for a field name.
     * @var array
     * @todo Support ENUM.
     */
    const CHANGE_MAP = [
        ['fieldDefinitions', 'hasChangedFieldDefinitionLabel', 'getChangeFieldDefinitionLabelActions'],
        ['fieldDefinitions', 'hasNewFieldDefinition', 'getAddFieldDefinitionActions'],
        ['fieldDefinitions', 'hasRemovedFieldDefinition', 'getRemoveFieldDefinitionActions'],
        // The order can only be matched, if everything is removed or added.
        ['fieldDefinitions', 'hasChangedFieldDefinitionOrder', 'getChangeFieldDefinitionOrderAction'],
        ['description', 'isDescriptionChanged', 'getDescriptionChangeAction'],
        ['name', 'isNameChanged', 'getNameChangeAction']
    ];

    /**
     * The commercetools client.
     * @var void|Client
     */
    private $client = null;

    /**
     * The type collection of the saved custom types.
     * @var TypeCollection|void
     */
    protected $savedCustomTypes = null;

    /**
     * The cached config.
     * @var void|array
     */
    private $typesConfig = null;

    /**
     * Configures this command.
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('commercetools:process-custom-types')
            ->setDescription('Creates the defined custom types or deletes the old ones.')
            ->addArgument(
                'whitelist',
                InputArgument::OPTIONAL,
                'Pregex for whitelisting which custom type keys should be handled with this bundle.',
                ''
            );
    }

    /**
     * Removes the relevant custom types which are not found in the config anymore.
     * @param string $filter Filter the types with a regex by their key, to only work on allowed types.
     * @return array false or Error-Message per Type-Key if there is something wrong.
     */
    private function deleteOldCustomTypes(string $filter): array
    {
        $results = [];
        $structure = $this->getCustomTypesConfig();

        if ($filter) {
            $filter = '/' . preg_quote($filter, '/') . '/';
        }

        /** @var Type $savedType */
        foreach ($this->getSavedCustomTypes() as $savedType) {
            $key = $savedType->getKey();

            $results[$key] = true;

            if ((!$filter || preg_match($filter, $key)) && !@$structure[$key]) {
                $response = $this->getClient()->execute(TypeDeleteByKeyRequest::ofKeyAndVersion(
                    $key,
                    $savedType->getVersion()
                ));

                if (!($response instanceof ResourceResponse)) {
                    $results[$key] = ($response instanceof ErrorResponse)
                        ? sprintf('%s (%s)', $response->getMessage(), $response->getCorrelationId())
                        : false;
                }
            }
        }

        return $results;
    }

    /**
     * Deletes and inserts the custom types.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bar = new ProgressBar($output, count($this->getCustomTypesConfig()) + 1);

        $bar->start();

        $results = $this->deleteOldCustomTypes($input->getArgument('whitelist'));

        $bar->advance();

        $results = array_merge($results, $this->saveCustomTypes($bar));

        $bar->finish();

        return $this->renderResultsTable($output, $results);
    }

    /**
     * Returns an array of add actions for field definitions.
     * @param array $newDefs
     * @param FieldDefinitionCollection $oldDefs
     * @return TypeAddFieldDefinitionAction[]
     */
    private function getAddFieldDefinitionActions(
        array $newDefs,
        FieldDefinitionCollection $oldDefs
    ): array {
        $actions = [];

        foreach ($newDefs as $fieldDefinition) {
            if (!$oldDefs->getByName($fieldDefinition['name'])) {
                $actions[] = (new TypeAddFieldDefinitionAction())
                    ->setFieldDefinition(FieldDefinition::fromArray($fieldDefinition));
            }
        }

        return $actions;
    }

    /**
     * Returns actions for changing the field definition labels.
     * @param array $newFields
     * @param FieldDefinitionCollection $savedFields
     * @return TypeChangeLabelAction[]
     */
    private function getChangeFieldDefinitionLabelActions(
        array $newFields,
        FieldDefinitionCollection $savedFields
    ): array {
        $actions = [];

        foreach ($newFields as $fieldDefinition) {
            if ($savedDef = $savedFields->getByName($fieldDefinition['name'])) {
                $savedLabel = $savedDef->getLabel()->toArray();
                $newLabel = $fieldDefinition['label'];

                sort($savedLabel);
                sort($newLabel);

                if ($savedLabel !== $newLabel) {
                    $actions[] = (new TypeChangeLabelAction())
                        ->setFieldName($savedDef->getName())
                        ->setLabel(LocalizedString::fromArray($fieldDefinition['label']));
                }
            }
        }

        return $actions;
    }

    /**
     * Returns actions for changing the field definition order.
     * @param array $newFields
     * @return TypeChangeFieldDefinitionOrderAction
     */
    private function getChangeFieldDefinitionOrderAction(array $newFields): TypeChangeFieldDefinitionOrderAction
    {
        $fieldNames = array_map(function (array $fieldDefinition) {
            return $fieldDefinition['name'];
        }, $newFields);

        return (new TypeChangeFieldDefinitionOrderAction())->setFieldNames($fieldNames);
    }

    /**
     * Returns the commercetools client.
     * @return Client
     */
    public function getClient(): Client
    {
        if (!$this->client) {
            $this->setClient($this->getContainer()->get('best_it_ct_custom_types.client'));
        }

        return $this->client;
    }

    /**
     * Returns the parsed config for the custom types.
     * @return array
     */
    public function getCustomTypesConfig(): array
    {
        if ($this->typesConfig === null) {
            $this->typesConfig = $this->parseCustomTypeStructure(
                $this->getContainer()->getParameter('best_it_ct_custom_types.types')
            );
        }

        return $this->typesConfig;
    }

    /**
     * Returns the change action for the description.
     * @param array $newDesc
     * @return TypeSetDescriptionAction
     */
    private function getDescriptionChangeAction(array $newDesc): TypeSetDescriptionAction
    {
        return (new TypeSetDescriptionAction())->setDescription(LocalizedString::fromArray($newDesc));
    }

    /**
     * Returns the name change action.
     * @param array $newName
     * @return TypeChangeNameAction
     */
    private function getNameChangeAction(array $newName): TypeChangeNameAction
    {
        return (new TypeChangeNameAction())->setName(LocalizedString::fromArray($newName));
    }

    /**
     * Returns an array of remove actions for field definitions.
     * @param array $newDefs
     * @param FieldDefinitionCollection $oldDefs
     * @return TypeRemoveFieldDefinitionAction[]
     */
    private function getRemoveFieldDefinitionActions(
        array $newDefs,
        FieldDefinitionCollection $oldDefs
    ): array {
        $actions = [];

        /** @var FieldDefinition $fieldDefinition */
        foreach ($oldDefs as $fieldDefinition) {
            $matchedDefinition = array_filter($newDefs, function (array $newFieldDefinition) use ($fieldDefinition) {
                return $newFieldDefinition['name'] === $fieldDefinition->getName();
            });

            if (!$matchedDefinition) {
                $actions[] = (new TypeRemoveFieldDefinitionAction())->setFieldName($fieldDefinition->getName());
            }
        }

        return $actions;
    }

    /**
     * Returns the saved custom types.
     * @return TypeCollection
     */
    public function getSavedCustomTypes(): TypeCollection
    {
        if (!$this->savedCustomTypes) {
            $this->setSavedCustomTypes($this->loadCustomTypes());
        }

        return $this->savedCustomTypes;
    }

    /**
     * Returns true, if there are changed field definition labels.
     * @param array $newFields
     * @param FieldDefinitionCollection $savedFields
     * @return bool
     */
    private function hasChangedFieldDefinitionLabel(
        array $newFields,
        FieldDefinitionCollection $savedFields
    ): bool {
        $return = false;

        foreach ($newFields as $fieldDefinition) {
            if ($savedDef = $savedFields->getByName($fieldDefinition['name'])) {
                $savedLabel = $savedDef->getLabel()->toArray();
                $newLabel = $fieldDefinition['label'];

                sort($savedLabel);
                sort($newLabel);

                if ($return = $savedLabel !== $newLabel) {
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * Returns true, if there is a changed field definition order.
     * @param array $newFields
     * @param FieldDefinitionCollection $savedFields
     * @return bool
     * @todo Deleting and sorting in the same request could fail.
     */
    private function hasChangedFieldDefinitionOrder(
        array $newFields,
        FieldDefinitionCollection $savedFields
    ): bool {
        $return = false;

        if (count($newFields) > 1) {
            foreach ($newFields as $index => $fieldDefinition) {
                $foundDefByIndex = $savedFields->getAt($index);
                $foundDefByName = $savedFields->getByName($fieldDefinition['name']);

                if ($foundDefByName &&
                    (!$foundDefByIndex || $foundDefByName->getName() !== $foundDefByIndex->getName())
                ) {
                    $return = true;
                }
            }
        }

        return $return;
    }

    /**
     * Returns true, if there are new field definitions.
     * @param array $newFields
     * @param FieldDefinitionCollection $savedFields
     * @return bool
     */
    private function hasNewFieldDefinition(
        array $newFields,
        FieldDefinitionCollection $savedFields
    ): bool {
        $return = false;

        foreach ($newFields as $fieldDefinition) {
            if (!$savedFields->getByName($fieldDefinition['name'])) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    /**
     * Returns true, if there are removed field definitions.
     * @param array $newFields
     * @param FieldDefinitionCollection $savedFields
     * @return bool
     */
    private function hasRemovedFieldDefinition(
        array $newFields,
        FieldDefinitionCollection $savedFields
    ): bool {
        $return = false;

        /** @var FieldDefinition $fieldDefinition */
        foreach ($savedFields as $fieldDefinition) {
            $matchedDefinition = array_filter($newFields, function (array $newFieldDefinition) use ($fieldDefinition) {
                return $newFieldDefinition['name'] === $fieldDefinition->getName();
            });

            if (!$matchedDefinition) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    /**
     * Returns true, if the value is changed.
     * @param array $newValue
     * @param LocalizedString $oldValue
     * @return bool
     */
    private function isDescriptionChanged(array $newValue, LocalizedString $oldValue): bool
    {
        $oldValue = $oldValue->toArray();

        sort($newValue);
        sort($oldValue);

        return $newValue !== $oldValue;
    }

    /**
     * Returns true, if the name is changed.
     * @param array $newName
     * @param LocalizedString $savedName
     * @return bool
     */
    private function isNameChanged(array $newName, LocalizedString $savedName): bool
    {
        $savedName = $savedName->toArray();

        sort($newName);
        sort($savedName);

        return $newName !== $savedName;
    }

    /**
     * Loads the custom types from the database.
     * @return TypeCollection
     */
    private function loadCustomTypes(): TypeCollection
    {
        $response = $this->getClient()->execute(TypeQueryRequest::of());
        $collection = new TypeCollection();

        // TODO Exit on Error.

        if (($response instanceof PagedQueryResponse) && $response->getCount()) {
            $collection = $response->toObject();
        }

        return $collection;
    }

    /**
     * Moves the array key to an array value.
     * @param array $structure
     * @return array
     * @todo Support more types.
     */
    private function parseCustomTypeStructure(array $structure): array
    {
        array_walk($structure, function (&$type, $typeKey) {
            $type['key'] = $typeKey;

            array_walk($type['fieldDefinitions'], function (&$typeField, $fieldName) {
                $typeField['name'] = $fieldName;

                if (!$typeField['type']['values']) {
                    unset($typeField['type']['values']);
                } else {
                    $values = [];

                    array_walk($typeField['type']['values'], function ($label, $key) use (&$values) {
                        $values[] = ['key' => $key, 'label' => $label];
                    });

                    $typeField['type']['values'] = $values;
                }

                if (($typeField['type']['name'] === 'Set') && (@$typeField['type']['elementType']['values'])) {
                    $values = [];

                    array_walk($typeField['type']['elementType']['values'], function ($label, $key) use (&$values) {
                        $values[] = ['key' => $key, 'label' => $label];
                    });

                    $typeField['type']['elementType']['values'] = $values;
                }
            });

            // Remove the string keys.
            $type['fieldDefinitions'] = array_values($type['fieldDefinitions']);
        });

        return $structure;
    }

    /**
     * Iterates thru the change map and creates the actions.
     * @param array $newType
     * @param Type $savedType
     * @return AbstractAction[]
     */
    private function processChangeMapOnSavedType(array $newType, Type $savedType): array
    {
        $actions = [];

        foreach (self::CHANGE_MAP as $rules) {
            $newValue = $newType[$rules[0]];
            $savedValue = $savedType->get($rules[0]);

            if (call_user_func([$this, $rules[1]], $newValue, $savedValue)) {
                $newActions = call_user_func([$this, $rules[2]], $newValue, $savedValue);

                $actions = array_merge($actions, is_array($newActions) ? $newActions : [$newActions]);
            }
        }

        return $actions;
    }

    /**
     * Renders the result table for the console.
     * @param OutputInterface $output
     * @param array $results
     * @return int 1 For a "cli error".
     */
    private function renderResultsTable(OutputInterface $output, array $results): int
    {
        $output->write(PHP_EOL . PHP_EOL);

        $return = 0;
        $table = new Table($output);

        $table->setHeaders(['type', 'status']);

        foreach ($results as $typeId => $typeStatus) {
            if ($error = $typeStatus !== true) {
                $return = 1;
            }

            $table->addRow([
                $typeId,
                sprintf(
                    !$error ? '<info>%s</info>' : '<comment>%s</comment>',
                    !$error ? 'Success' : $typeStatus
                )
            ]);
        }

        $table->render();

        return $return;
    }

    /**
     * Saves the given custom type.
     * @param array $customType
     * @return bool|string
     */
    private function saveCustomType(array $customType)
    {
        $collection = $this->getSavedCustomTypes();
        $createRequest = null;
        $response = null;
        $requiredKey = $customType['key'];
        $saved = true;

        /** @var Type $savedType */
        if ($savedType = $collection->getBy('key', $requiredKey)) {
            $actions = $this->processChangeMapOnSavedType($customType, $savedType);

            $response = $this->getClient()->execute(
                TypeUpdateByKeyRequest::ofKeyAndVersion($savedType->getKey(), $savedType->getVersion())
                    ->setActions($actions)
            );
        } else {
            $response = $this->getClient()->execute(
                TypeCreateRequest::ofDraft(
                    TypeDraft::ofKeyNameDescriptionAndResourceTypes(
                        $requiredKey,
                        LocalizedString::fromArray($customType['name'] ?? []),
                        LocalizedString::fromArray($customType['description'] ?? []),
                        $customType['resourceTypeIds']
                    )->setFieldDefinitions(FieldDefinitionCollection::fromArray($customType['fieldDefinitions']))
                )
            );
        }

        if ($response && !($response instanceof ResourceResponse)) {
            $saved = ($response instanceof ErrorResponse)
                ? sprintf('%s (%s)', $response->getMessage(), $response->getCorrelationId())
                : false;
        }
        return $saved;
    }


    /**
     * Saves the custom types from the config to the database.
     * @param ProgressBar $bar
     * @return array false or Error-Message per Type-Key if there is something wrong.
     */
    private function saveCustomTypes(ProgressBar $bar): array
    {
        $results = [];

        foreach ($this->getCustomTypesConfig() as $customType) {
            $saved = $this->saveCustomType($customType);

            $results[$customType['key']] = $saved;

            $bar->advance();
        }
        return $results;
    }

    /**
     * Sets the commercetools client.
     * @param Client $client
     * @return ProcessCTCustomTypesCommand
     */
    private function setClient(Client $client): ProcessCTCustomTypesCommand
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Sets the saved custom types.
     * @param TypeCollection $savedCustomTypes
     * @return ProcessCTCustomTypesCommand
     */
    private function setSavedCustomTypes(TypeCollection $savedCustomTypes): ProcessCTCustomTypesCommand
    {
        $this->savedCustomTypes = $savedCustomTypes;

        return $this;
    }
}
