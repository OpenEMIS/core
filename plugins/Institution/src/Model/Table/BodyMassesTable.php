<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class BodyMassesTable extends AppTable
{
    const ASSIGNED = 1;

    public function initialize(array $config)
    {
        $this->table('body_masses');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'user_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('height', 'validHeight', [
                'rule' => ['range', 0, 3]
            ])
            ->add('weight', 'validWeight', [
                'rule' => ['range', 0, 500]
            ])
            ->add('date', 'dateWithinPeriod', [
                'rule' => function ($value, $context) {
                    $inputDate = new Date ($value);

                    if (!empty($context['data']['academic_period_id'])) {
                        $academicPeriodEntity = $this->AcademicPeriods->get($context['data']['academic_period_id']);
                        $academicStartDate = $academicPeriodEntity->start_date;
                        $academicEndDate = $academicPeriodEntity->end_date;

                        if ($inputDate >= $academicStartDate && $inputDate <= $academicEndDate) {
                            return true;
                        } else {
                            $startDate = date('d-m-Y', strtotime($academicStartDate));
                            $endDate = date('d-m-Y', strtotime($academicEndDate));

                            return $this->getMessage('BodyMasses.dateNotWithinPeriod', ['sprintf' => [$startDate, $endDate]]);
                        }
                    } else {
                        return true;
                    }
                }
            ])
        ;
    }

    public function findIndex(Query $query, array $options)
    {
        $query->where(['user_id' => $options['querystring']['student_id']]);
        if (array_key_exists('sort', $options) && $options['sort'] == 'date') {
            $direction = $options['direction'];
            $query->order([$this->aliasField($options['sort']) => $direction, $this->aliasField('created') => 'desc']);

        }
        return $query;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (!empty($data['height']) && !empty($data['weight'])) {
            $height = $data['height'];
            $weight = $data['weight'];

            $bmi = round(($weight / ($height * $height)), 2);
            $data['body_mass_index'] = $bmi;
        }
    }
}
