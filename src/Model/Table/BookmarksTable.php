<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class BookmarksTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('bookmarks');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
        $this->belongsToMany('Tags', [
            'foreignKey' => 'bookmark_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'bookmarks_tags'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->scalar('title')
            ->maxLength('title', 50)
            ->allowEmptyString('title');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('url')
            ->allowEmptyString('url');

        return $validator;
    }

    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    // $query 引数は、クエリービルダーのインスタンスです。
    // $options 配列には、コントローラーのアクション中で find('tagged') に渡した
    // 'tag' オプションが含まれます。
    public function findTagged(Query $query, array $options)
    {
        $bookmarks = $this->find()
            ->select(['id', 'url', 'title', 'description']);

        if (empty($options['tags'])) {
            $bookmarks
                ->leftJoinWith('Tags')
                ->where(['Tags.title IS' => null]);
        } else {
            $bookmarks
                ->innerJoinWith('Tags')
                ->where(['Tags.title IN ' => $options['tags']]);
        }

        return $bookmarks->group(['Bookmarks.id']);
    }

    public function beforeSave($event, $entity, $options)
    {
        if($entity->tag_string){
            $entity->tags = $this->_buildTags($entity->tag_string);
        }
    }

    protected function _buildTags($tagString)
    {
        // タグにtrimを適用
        $newTags = array_map('trim', explode(',', $tagString));
        // すべての空のタグを削除
        $newTags = array_filter($newTags);
        // 重複するタグの削除
        $newTags = array_unique($newTags);

        $out = [];
        $query = $this->Tags->find()->where(['Tags.tile IN' => $newTags]);

        // 新しいタグの一覧から既存のタグを削除
        foreach($query->extract('title') as $existing){
            $index = array_search($existing, $newTags);
            if($index !== false){
                unset($newTags[$index]);
            }
        }

        // 既存のタグの追加
        foreach($query as $tag){
            $out[] = $tag;
        }
        // 新しいタグの追加
        foreach ($newTags as $tag) {
            $out[] = $this->Tags->newEntity(['title' => $tag]);
        }
        return $out;
    }
}
