<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class UserBodyMassesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('user_body_masses');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('height', [
                'notZero' => [
                    'rule' => ['comparison', '>', 0],
                    'last' => true
                ],
                'validHeight' => [
                    'rule' => ['range', 0, 300],
                    'last' => true
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                ],
            ])
            ->add('weight', [
                'notZero' => [
                    'rule' => ['comparison', '>', 0],
                    'last' => true
                ],
                'validWeight' => [
                    'rule' => ['range', 0, 500],
                    'last' => true
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                ],
            ])
            ->add('date', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => ['security_user_id', 'date']]],
                    'provider' => 'table'
                ],
                'dateWithinPeriod' => [
                    'rule' => function ($value, $context) {
                        $inputDate = new Date ($value);

                        if (!empty($context['data']['academic_period_id'])) {
                            $academicPeriodEntity = $this->AcademicPeriods
                                ->find()
                                ->where([$this->AcademicPeriods->aliasField('id') => $context['data']['academic_period_id']])
                                ->first();

                            if (!is_null($academicPeriodEntity)) {
                                $academicStartDate = $academicPeriodEntity->start_date;
                                $academicEndDate = $academicPeriodEntity->end_date;

                                if ($inputDate >= $academicStartDate && $inputDate <= $academicEndDate) {
                                    return true;
                                } else {
                                    $startDate = date('d-m-Y', strtotime($academicStartDate));
                                    $endDate = date('d-m-Y', strtotime($academicEndDate));

                                    return $this->getMessage('UserBodyMasses.dateNotWithinPeriod', ['sprintf' => [$startDate, $endDate]]);
                                }
                            } else {
                                return __('Invalid academic period');
                            }
                        } else {
                            return true;
                        }
                    },
                ],    
            ]);
    }

    public function findIndex(Query $query, array $options)
    {
        if (array_key_exists('sort', $options) && $options['sort'] == 'date') {
            $direction = $options['direction'];
            $query->order([$this->aliasField($options['sort']) => $direction, $this->aliasField('created') => 'desc']);

        }
        return $query;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (!empty($data['height']) && !empty($data['weight'])) {
            $height = round($data['height']/100, 2);
            $weight = round($data['weight'], 2);

            $denominator = $height * $height;

            // to prevent the division by 0
            if ($denominator > 0) {
                $bmi = round(($weight / ($denominator)), 2);
            } else {
                $bmi = 0;
            }

            $data['body_mass_index'] = $bmi;
        }
    }
}
