<?php

namespace Spatie\QueryBuilder\Includes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IncludedRelationship implements IncludeInterface
{
    public function __invoke(Builder $query, string $relationship)
    {
        $relatedTables = collect(explode('.', $relationship));

        $withs = $relatedTables
            ->mapWithKeys(function ($table, $key) use ($query, $relatedTables) {
                $fullRelationName = $relatedTables->slice(0, $key + 1)->implode('.');

				$tmpname = '_'.str_replace('.','-',$fullRelationName);
				$param = $query->getRequestedForRelatedTable($tmpname);
				//dump($param);
				if(empty($param)){
					return [$fullRelationName];
				}else{
					return [$fullRelationName => function ($query) use ($param) {

						if(!empty($param['fields'])){
							$query = $query->select(str2arr($param['fields']));
						}

						if(!empty($param['sort'])){
							$sort = ltrim($param['sort'], '-');
							if($sort && strpos($param['sort'],'-')==0){
								$query = $query->sortBy($sort,'desc');
							}else{
								$query = $query->sortBy($sort,'asc');
							}
						}
						if(!empty($param['limit'])){
							$query = $query->limit($param['limit']);
						}
					}];
				}                
                
/*
                $fields = $query->getRequestedFieldsForRelatedTable($fullRelationName);

                if (empty($fields)) {
                    return [$fullRelationName];
                }

                return [$fullRelationName => function ($query) use ($fields) {
                    $query->select($fields);
                }];*/
            })
            ->toArray();

        $query->with($withs);
    }

    public static function getIndividualRelationshipPathsFromInclude(string $include): Collection
    {
        return collect(explode('.', $include))
            ->reduce(function ($includes, $relationship) {
                if ($includes->isEmpty()) {
                    return $includes->push($relationship);
                }

                return $includes->push("{$includes->last()}.{$relationship}");
            }, collect());
    }
}
