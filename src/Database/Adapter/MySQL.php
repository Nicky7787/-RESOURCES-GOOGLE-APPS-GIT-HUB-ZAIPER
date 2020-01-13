<?php

namespace Database\Adapter;

use Utopia\Registry\Registry;
use Exception;
use PDO;
use Redis as Client;
use Database\Adapter;
use Database\Validator\Authorization;

class MySQL extends Adapter
{
    const DATA_TYPE_STRING = 'string';
    const DATA_TYPE_INTEGER = 'integer';
    const DATA_TYPE_FLOAT = 'float';
    const DATA_TYPE_BOOLEAN = 'boolean';
    const DATA_TYPE_OBJECT = 'object';
    const DATA_TYPE_DICTIONARY = 'dictionary';
    const DATA_TYPE_ARRAY = 'array';
    const DATA_TYPE_NULL = 'null';

    const OPTIONS_LIMIT_ATTRIBUTES = 1000;

    /**
     * @var PDO
     */
    protected $register;

    /**
     * Saved nodes.
     *
     * @var array
     */
    protected $nodes = [];

    /**
     * Count documents get usage.
     *
     * @var int
     */
    protected $count = 0;

    /**
     * Last modified.
     *
     * Read node with most recent changes
     *
     * @var int
     */
    protected $lastModified = -1;

    /**
     * @var array
     */
    protected $debug = [];

    /**
     * Constructor.
     *
     * Set connection and settings
     *
     * @param Registry $register
     */
    public function __construct(Registry $register)
    {
        $this->register = $register;
    }

    /**
     * Get Document.
     *
     * @param string $id
     *
     * @return array
     *
     * @throws Exception
     */
    public function getDocument($id)
    {
        ++$this->count;

        // Get fields abstraction
        $st = $this->getPDO()->prepare('SELECT * FROM `'.$this->getNamespace().'.database.documents` a
            WHERE a.uid = :uid AND a.status = 0
            ORDER BY a.updatedAt DESC LIMIT 10;
        ');

        $st->bindValue(':uid', $id, PDO::PARAM_STR);

        $st->execute();

        $document = $st->fetch();

        if (empty($document)) { // Not Found
            return [];
        }

        // Get fields abstraction
        $st = $this->getPDO()->prepare('SELECT * FROM `'.$this->getNamespace().'.database.properties` a
            WHERE a.documentUid = :documentUid AND a.documentRevision = :documentRevision
              ORDER BY `order`
        ');

        $st->bindParam(':documentUid', $document['uid'], PDO::PARAM_STR);
        $st->bindParam(':documentRevision', $document['revision'], PDO::PARAM_STR);

        $st->execute();

        $properties = $st->fetchAll();

        $output = [
            '$uid' => null,
            '$collection' => null,
            '$permissions' => (!empty($document['permissions'])) ? json_decode($document['permissions'], true) : [],
        ];

        foreach ($properties as &$property) {
            settype($property['value'], $property['primitive']);

            if ($property['array']) {
                $output[$property['key']][] = $property['value'];
            } else {
                $output[$property['key']] = $property['value'];
            }
        }

        // Get fields abstraction
        $st = $this->getPDO()->prepare('SELECT * FROM `'.$this->getNamespace().'.database.relationships` a
            WHERE a.start = :start AND revision = :revision
              ORDER BY `order`
        ');

        $st->bindParam(':start', $document['uid'], PDO::PARAM_STR);
        $st->bindParam(':revision', $document['revision'], PDO::PARAM_STR);

        $st->execute();

        $output['temp-relations'] = $st->fetchAll();

        return $output;
    }

    /**
     * Create Document.
     *
     * @param array $data
     *
     * @throws \Exception
     *
     * @return array
     */
    public function createDocument(array $data = [])
    {
        $order = 0;
        $data = array_merge(['$uid' => null, '$permissions' => []], $data); // Merge data with default params
        $signature = md5(json_encode($data, true));
        $revision = uniqid('', true);

        /*
         * When updating node, check if there are any changes to update
         *  by comparing data md5 signatures
         */
        if (null !== $data['$uid']) {
            $st = $this->getPDO()->prepare('SELECT signature FROM `'.$this->getNamespace().'.database.documents` a
                    WHERE a.uid = :uid AND a.status = 0
                    ORDER BY a.updatedAt DESC LIMIT 1;
                ');

            $st->bindValue(':uid', $data['$uid'], PDO::PARAM_STR);

            $st->execute();

            $oldSignature = $st->fetch()['signature'];

            if ($signature === $oldSignature) {
                return $data;
            }
        }

        // Add or update fields abstraction level
        $st1 = $this->getPDO()->prepare('INSERT INTO `'.$this->getNamespace().'.database.documents`
            SET uid = :uid, createdAt = :createdAt, updatedAt = :updatedAt, signature = :signature, revision = :revision, permissions = :permissions, status = 0
            ON DUPLICATE KEY UPDATE uid = :uid, updatedAt = :updatedAt, signature = :signature, revision = :revision, permissions = :permissions;
		');

        // Adding fields properties
        if (null === $data['$uid'] || !isset($data['$uid'])) { // Get new fields UID
            $data['$uid'] = $this->getUid();
        }

        $st1->bindValue(':uid', $data['$uid'], PDO::PARAM_STR);
        $st1->bindValue(':revision', $revision, PDO::PARAM_STR);
        $st1->bindValue(':signature', $signature, PDO::PARAM_STR);
        $st1->bindValue(':createdAt', date('Y-m-d H:i:s', time()), PDO::PARAM_STR);
        $st1->bindValue(':updatedAt', date('Y-m-d H:i:s', time()), PDO::PARAM_STR);
        $st1->bindValue(':permissions', json_encode($data['$permissions']), PDO::PARAM_STR);

        $st1->execute();

        // Delete old properties
        $rms1 = $this->getPDO()->prepare('DELETE FROM `'.$this->getNamespace().'.database.properties` WHERE documentUid = :documentUid AND documentRevision != :documentRevision');
        $rms1->bindValue(':documentUid', $data['$uid'], PDO::PARAM_STR);
        $rms1->bindValue(':documentRevision', $revision, PDO::PARAM_STR);
        $rms1->execute();

        // Delete old relationships
        $rms2 = $this->getPDO()->prepare('DELETE FROM `'.$this->getNamespace().'.database.relationships` WHERE start = :start AND revision != :revision');
        $rms2->bindValue(':start', $data['$uid'], PDO::PARAM_STR);
        $rms2->bindValue(':revision', $revision, PDO::PARAM_STR);
        $rms2->execute();

        // Create new properties
        $st2 = $this->getPDO()->prepare('INSERT INTO `'.$this->getNamespace().'.database.properties`
                    (`documentUid`, `documentRevision`, `key`, `value`, `primitive`, `array`, `order`)
                VALUES (:documentUid, :documentRevision, :key, :value, :primitive, :array, :order)');

        $props = [];

        foreach ($data as $key => $value) { // Prepare properties data

            if (in_array($key, ['$permissions'])) {
                continue;
            }

            $type = $this->getDataType($value);

            // Handle array of relations
            if (self::DATA_TYPE_ARRAY === $type) {
                foreach ($value as $i => $child) {
                    if (self::DATA_TYPE_DICTIONARY !== $this->getDataType($child)) { // not dictionary

                        $props[] = [
                            'type' => $this->getDataType($child),
                            'key' => $key,
                            'value' => $child,
                            'array' => true,
                            'order' => $order++,
                        ];

                        continue;
                    }

                    $data[$key][$i] = $this->createDocument($child);

                    $this->createRelationship($revision, $data['$uid'], $data[$key][$i]['$uid'], $key, true, $i);
                }

                continue;
            }

            // Handle relation
            if (self::DATA_TYPE_DICTIONARY === $type) {
                $value = $this->createDocument($value);
                $this->createRelationship($revision, $data['$uid'], $value['$uid'], $key); //xxx
                continue;
            }

            // Handle empty values
            if (self::DATA_TYPE_NULL === $type) {
                continue;
            }

            $props[] = [
                'type' => $type,
                'key' => $key,
                'value' => $value,
                'array' => false,
                'order' => $order++,
            ];
        }

        foreach ($props as $prop) {
            if (is_array($prop['value'])) {
                throw new Exception('Value can\'t be an array: '.json_encode($prop['value']));
            }
            $st2->bindValue(':documentUid', $data['$uid'], PDO::PARAM_STR);
            $st2->bindValue(':documentRevision', $revision, PDO::PARAM_STR);

            $st2->bindValue(':key', $prop['key'], PDO::PARAM_STR);
            $st2->bindValue(':value', $prop['value'], PDO::PARAM_STR);
            $st2->bindValue(':primitive', $prop['type'], PDO::PARAM_STR);
            $st2->bindValue(':array', $prop['array'], PDO::PARAM_BOOL);
            $st2->bindValue(':order', $prop['order'], PDO::PARAM_STR);

            $st2->execute();
        }

        //TODO remove this dependency (check if related to nested documents)
        $this->getRedis()->expire($this->getNamespace().':document-'.$data['$uid'], 0);
        $this->getRedis()->expire($this->getNamespace().':document-'.$data['$uid'], 0);

        return $data;
    }

    /**
     * Update Document.
     *
     * @param array $data
     *
     * @return array
     *
     * @throws Exception
     */
    public function updateDocument(array $data = [])
    {
        return $this->createDocument($data);
    }

    /**
     * Delete Document.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws Exception
     */
    public function deleteDocument($id)
    {
        $st1 = $this->getPDO()->prepare('DELETE FROM `'.$this->getNamespace().'.database.documents`
            WHERE uid = :id
		');

        $st1->bindValue(':id', $id, PDO::PARAM_STR);

        $st1->execute();

        $st2 = $this->getPDO()->prepare('DELETE FROM `'.$this->getNamespace().'.database.properties`
            WHERE documentUid = :id
		');

        $st2->bindValue(':id', $id, PDO::PARAM_STR);

        $st2->execute();

        $st3 = $this->getPDO()->prepare('DELETE FROM `'.$this->getNamespace().'.database.relationships`
            WHERE start = :id OR end = :id
		');

        $st3->bindValue(':id', $id, PDO::PARAM_STR);

        $st3->execute();

        return [];
    }

    /**
     * Create Relation.
     *
     * Adds a new relationship between different nodes
     *
     * @param string $revision
     * @param int    $start
     * @param int    $end
     * @param string $key
     * @param bool   $isArray
     * @param int    $order
     *
     * @return array
     *
     * @throws Exception
     */
    protected function createRelationship($revision, $start, $end, $key, $isArray = false, $order = 0)
    {
        $st2 = $this->getPDO()->prepare('INSERT INTO `'.$this->getNamespace().'.database.relationships`
                (`revision`, `start`, `end`, `key`, `array`, `order`)
            VALUES (:revision, :start, :end, :key, :array, :order)');

        $st2->bindValue(':revision', $revision, PDO::PARAM_STR);
        $st2->bindValue(':start', $start, PDO::PARAM_STR);
        $st2->bindValue(':end', $end, PDO::PARAM_STR);
        $st2->bindValue(':key', $key, PDO::PARAM_STR);
        $st2->bindValue(':array', $isArray, PDO::PARAM_INT);
        $st2->bindValue(':order', $order, PDO::PARAM_INT);

        $st2->execute();

        return [];
    }

    /**
     * Create Namespace.
     *
     * @param $namespace
     *
     * @throws Exception
     *
     * @return bool
     */
    public function createNamespace($namespace)
    {
        if (empty($namespace)) {
            throw new Exception('Empty namespace');
        }

        $documents = 'app_'.$namespace.'.database.documents';
        $properties = 'app_'.$namespace.'.database.properties';
        $relationships = 'app_'.$namespace.'.database.relationships';
        $audit = 'app_'.$namespace.'.audit.audit';
        $abuse = 'app_'.$namespace.'.abuse.abuse';

        try {
            $this->getPDO()->prepare('CREATE TABLE `'.$documents.'` LIKE `template.database.documents`;')->execute();
            $this->getPDO()->prepare('CREATE TABLE `'.$properties.'` LIKE `template.database.properties`;')->execute();
            $this->getPDO()->prepare('CREATE TABLE `'.$relationships.'` LIKE `template.database.relationships`;')->execute();
            $this->getPDO()->prepare('CREATE TABLE `'.$audit.'` LIKE `template.audit.audit`;')->execute();
            $this->getPDO()->prepare('CREATE TABLE `'.$abuse.'` LIKE `template.abuse.abuse`;')->execute();
        } catch (Exception $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Delete Namespace.
     *
     * @param $namespace
     *
     * @throws Exception
     *
     * @return bool
     */
    public function deleteNamespace($namespace)
    {
        if (empty($namespace)) {
            throw new Exception('Empty namespace');
        }

        $documents = 'app_'.$namespace.'.database.documents';
        $properties = 'app_'.$namespace.'.database.properties';
        $relationships = 'app_'.$namespace.'.database.relationships';
        $audit = 'app_'.$namespace.'.audit.audit';
        $abuse = 'app_'.$namespace.'.abuse.abuse';

        try {
            $this->getPDO()->prepare('DROP TABLE `'.$documents.'`;')->execute();
            $this->getPDO()->prepare('DROP TABLE `'.$properties.'`;')->execute();
            $this->getPDO()->prepare('DROP TABLE `'.$relationships.'`;')->execute();
            $this->getPDO()->prepare('DROP TABLE `'.$audit.'`;')->execute();
            $this->getPDO()->prepare('DROP TABLE `'.$abuse.'`;')->execute();
        } catch (Exception $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Get Collection.
     *
     * @param array $options
     *
     * @throws Exception
     *
     * @return array
     */
    public function getCollection(array $options)
    {
        $start = microtime(true);
        $orderCastMap = [
            'int' => 'UNSIGNED',
            'string' => 'CHAR',
            'date' => 'DATE',
            'time' => 'TIME',
            'datetime' => 'DATETIME',
        ];
        $orderTypeMap = ['DESC', 'ASC'];

        $options['orderField'] = (empty($options['orderField'])) ? '$uid' : $options['orderField']; // Set default order field
        $options['orderCast'] = (empty($options['orderCast'])) ? 'string' : $options['orderCast']; // Set default order field

        if (!array_key_exists($options['orderCast'], $orderCastMap)) {
            throw new Exception('Invalid order cast');
        }

        if (!in_array($options['orderType'], $orderTypeMap)) {
            throw new Exception('Invalid order type');
        }

        $where = [];
        $join = [];
        $sorts = [];
        $search = '';

        // Filters
        foreach ($options['filters'] as $i => $filter) {
            $filter = $this->parseFilter($filter);
            $key = $filter['key'];
            $value = $filter['value'];
            $operator = $filter['operator'];

            $path = explode('.', $key);
            $original = $path;

            if (1 < count($path)) {
                $key = array_pop($path);
            } else {
                $path = [];
            }

            //$path = implode('.', $path);

            $key = $this->getPDO()->quote($key, PDO::PARAM_STR);
            $value = $this->getPDO()->quote($value, PDO::PARAM_STR);
            //$path               = $this->getPDO()->quote($path, PDO::PARAM_STR);
            $options['offset'] = (int) $options['offset'];
            $options['limit'] = (int) $options['limit'];

            if (empty($path)) {
                //if($path == "''") { // Handle direct attributes queries
                $where[] = 'JOIN `'.$this->getNamespace().".database.properties` b{$i} ON a.uid IS NOT NULL AND b{$i}.documentUid = a.uid AND (b{$i}.key = {$key} AND b{$i}.value {$operator} {$value})";
            } else { // Handle direct child attributes queries
                $len = count($original);
                $prev = 'c'.$i;

                foreach ($original as $y => $part) {
                    $part = $this->getPDO()->quote($part, PDO::PARAM_STR);

                    if (0 === $y) { // First key
                        $join[$i] = 'JOIN `'.$this->getNamespace().".database.relationships` c{$i} ON a.uid IS NOT NULL AND c{$i}.start = a.uid AND c{$i}.key = {$part}";
                    } elseif ($y == $len - 1) { // Last key
                        $join[$i] .= 'JOIN `'.$this->getNamespace().".database.properties` e{$i} ON e{$i}.documentUid = {$prev}.end AND e{$i}.key = {$part} AND e{$i}.value {$operator} {$value}";
                    } else {
                        $join[$i] .= 'JOIN `'.$this->getNamespace().".database.relationships` d{$i}{$y} ON d{$i}{$y}.start = {$prev}.end AND d{$i}{$y}.key = {$part}";
                        $prev = 'd'.$i.$y;
                    }
                }

                //$join[] = "JOIN `" . $this->getNamespace() . ".database.relationships` c{$i} ON a.uid IS NOT NULL AND c{$i}.start = a.uid AND c{$i}.key = {$path}
                //    JOIN `" . $this->getNamespace() . ".database.properties` d{$i} ON d{$i}.documentUid = c{$i}.end AND d{$i}.key = {$key} AND d{$i}.value {$operator} {$value}";
            }
        }

        // Sorting
        $orderPath = explode('.', $options['orderField']);
        $len = count($orderPath);
        $orderKey = 'order_b';
        $part = $this->getPDO()->quote(implode('', $orderPath), PDO::PARAM_STR);
        $orderSelect = "CASE WHEN {$orderKey}.key = {$part} THEN CAST({$orderKey}.value AS {$orderCastMap[$options['orderCast']]}) END AS sort_ff";

        if (1 === $len) {
            //if($path == "''") { // Handle direct attributes queries
            $sorts[] = 'LEFT JOIN `'.$this->getNamespace().".database.properties` order_b ON a.uid IS NOT NULL AND order_b.documentUid = a.uid AND (order_b.key = {$part})";
        } else { // Handle direct child attributes queries
            $prev = 'c';
            $orderKey = 'order_e';

            foreach ($orderPath as $y => $part) {
                $part = $this->getPDO()->quote($part, PDO::PARAM_STR);
                $x = $y - 1;

                if (0 === $y) { // First key
                    $sorts[] = 'JOIN `'.$this->getNamespace().".database.relationships` order_c{$y} ON a.uid IS NOT NULL AND order_c{$y}.start = a.uid AND order_c{$y}.key = {$part}";
                } elseif ($y == $len - 1) { // Last key
                    $sorts[] .= 'JOIN `'.$this->getNamespace().".database.properties` order_e ON order_e.documentUid = order_{$prev}{$x}.end AND order_e.key = {$part}";
                } else {
                    $sorts[] .= 'JOIN `'.$this->getNamespace().".database.relationships` order_d{$y} ON order_d{$y}.start = order_{$prev}{$x}.end AND order_d{$y}.key = {$part}";
                    $prev = 'd';
                }
            }
        }

        /*
         * Workaround for a MySQL bug as reported here:
         * https://bugs.mysql.com/bug.php?id=78485
         */
        $options['search'] = ($options['search'] === '*') ? '' : $options['search'];

        // Search
        if (!empty($options['search'])) { // Handle free search
//            $where[] = "LEFT JOIN `" . $this->getNamespace() . ".database.properties` b_search ON a.uid IS NOT NULL AND b_search.documentUid = a.uid
//                    LEFT JOIN
//                `" . $this->getNamespace() . ".database.relationships` c_search ON c_search.start = a.uid
//                    LEFT JOIN
//                `" . $this->getNamespace() . ".database.properties` d_search ON d_search.documentUid = c_search.end
//                    LEFT JOIN
//                `" . $this->getNamespace() . ".database.relationships` e_search ON e_search.start = c_search.end
//                    LEFT JOIN
//                `" . $this->getNamespace() . ".database.properties` f_search ON f_search.documentUid = e_search.end
//                \n";
//            $search = "AND (MATCH (b_search.value) AGAINST ({$this->getPDO()->quote($options['search'], PDO::PARAM_STR)} IN BOOLEAN MODE)
//                OR b_search.value LIKE {$this->getPDO()->quote('%%' . $options['search'] . '%%', PDO::PARAM_STR)}
//                OR MATCH (d_search.value) AGAINST ({$this->getPDO()->quote($options['search'], PDO::PARAM_STR)} IN BOOLEAN MODE)
//                OR d_search.value LIKE {$this->getPDO()->quote('%%' . $options['search'] . '%%', PDO::PARAM_STR)}
//                OR MATCH (f_search.value) AGAINST ({$this->getPDO()->quote($options['search'], PDO::PARAM_STR)} IN BOOLEAN MODE)
//                OR f_search.value LIKE {$this->getPDO()->quote('%%' . $options['search'] . '%%', PDO::PARAM_STR)})";

            $where[] = 'LEFT JOIN `'.$this->getNamespace().".database.properties` b_search ON a.uid IS NOT NULL AND b_search.documentUid = a.uid  AND b_search.primitive = 'string'
                    LEFT JOIN
                `".$this->getNamespace().'.database.relationships` c_search ON c_search.start = b_search.documentUid
                    LEFT JOIN
                `'.$this->getNamespace().".database.properties` d_search ON d_search.documentUid = c_search.end AND d_search.primitive = 'string'
                \n";

            $search = "AND (MATCH (b_search.value) AGAINST ({$this->getPDO()->quote($options['search'], PDO::PARAM_STR)} IN BOOLEAN MODE)
                OR MATCH (d_search.value) AGAINST ({$this->getPDO()->quote($options['search'], PDO::PARAM_STR)} IN BOOLEAN MODE)
            )";
        }

        $select = 'DISTINCT a.uid';
        $where = implode("\n", $where);
        $join = implode("\n", $join);
        $sorts = implode("\n", $sorts);
        $range = "LIMIT {$options['offset']}, {$options['limit']}";
        $roles = [];

        foreach (Authorization::getRoles() as $role) {
            $roles[] = 'JSON_CONTAINS(REPLACE(a.permissions, \'{self}\', a.uid), \'"'.$role.'"\', \'$.read\')';
        }

        if (false === Authorization::$status) { // FIXME temporary solution (hopefully)
            $roles = ['1=1'];
        }

        $query = "SELECT %s, {$orderSelect}
            FROM `".$this->getNamespace().".database.documents` a {$where}{$join}{$sorts}
            WHERE status = 0
               {$search}
               AND (".implode('||', $roles).")
            ORDER BY sort_ff {$options['orderType']} %s";

        $st = $this->getPDO()->prepare(sprintf($query, $select, $range));

        $st->execute();

        $results = ['data' => []];

        // Get entire fields data for each id
        foreach ($st->fetchAll() as $node) {
            $results['data'][] = $node['uid'];
        }

        $count = $this->getPDO()->prepare(sprintf($query, 'count(DISTINCT a.uid) as sum', ''));

        $count->execute();

        $count = $count->fetch();

        $this->resetDebug();

        $this
            ->setDebug('query', preg_replace('/\s+/', ' ', sprintf($query, $select, $range)))
            ->setDebug('time', microtime(true) - $start)
            ->setDebug('filters', count($options['filters']))
            ->setDebug('joins', substr_count($query, 'JOIN'))
            ->setDebug('count', count($results['data']))
            ->setDebug('sum', (int) $count['sum'])
            ->setDebug('documents', $this->count)
        ;

        return $results['data'];
    }

    /**
     * Get Collection.
     *
     * @param array $options
     *
     * @throws Exception
     *
     * @return int
     */
    public function getCount(array $options)
    {
        $start = microtime(true);
        $where = [];
        $join = [];

        // Filters
        foreach ($options['filters'] as $i => $filter) {
            $filter = $this->parseFilter($filter);
            $key = $filter['key'];
            $value = $filter['value'];
            $operator = $filter['operator'];
            $path = explode('.', $key);
            $original = $path;

            if (1 < count($path)) {
                $key = array_pop($path);
            } else {
                $path = [];
            }

            $key = $this->getPDO()->quote($key, PDO::PARAM_STR);
            $value = $this->getPDO()->quote($value, PDO::PARAM_STR);

            if (empty($path)) {
                //if($path == "''") { // Handle direct attributes queries
                $where[] = 'JOIN `'.$this->getNamespace().".database.properties` b{$i} ON a.uid IS NOT NULL AND b{$i}.documentUid = a.uid AND (b{$i}.key = {$key} AND b{$i}.value {$operator} {$value})";
            } else { // Handle direct child attributes queries
                $len = count($original);
                $prev = 'c'.$i;

                foreach ($original as $y => $part) {
                    $part = $this->getPDO()->quote($part, PDO::PARAM_STR);

                    if (0 === $y) { // First key
                        $join[$i] = 'JOIN `'.$this->getNamespace().".database.relationships` c{$i} ON a.uid IS NOT NULL AND c{$i}.start = a.uid AND c{$i}.key = {$part}";
                    } elseif ($y == $len - 1) { // Last key
                        $join[$i] .= 'JOIN `'.$this->getNamespace().".database.properties` e{$i} ON e{$i}.documentUid = {$prev}.end AND e{$i}.key = {$part} AND e{$i}.value {$operator} {$value}";
                    } else {
                        $join[$i] .= 'JOIN `'.$this->getNamespace().".database.relationships` d{$i}{$y} ON d{$i}{$y}.start = {$prev}.end AND d{$i}{$y}.key = {$part}";
                        $prev = 'd'.$i.$y;
                    }
                }
            }
        }

        $where = implode("\n", $where);
        $join = implode("\n", $join);
        $func = 'JOIN `'.$this->getNamespace().".database.properties` b_func ON a.uid IS NOT NULL
            AND a.uid = b_func.documentUid
            AND (b_func.key = 'sizeOriginal')";
        $roles = [];

        foreach (Authorization::getRoles() as $role) {
            $roles[] = 'JSON_CONTAINS(REPLACE(a.permissions, \'{self}\', a.uid), \'"'.$role.'"\', \'$.read\')';
        }

        if (false === Authorization::$status) { // FIXME temporary solution (hopefully)
            $roles = ['1=1'];
        }

        $query = 'SELECT SUM(b_func.value) as result
            FROM `'.$this->getNamespace().".database.documents` a {$where}{$join}{$func}
            WHERE status = 0
               AND (".implode('||', $roles).')';

        $st = $this->getPDO()->prepare(sprintf($query));

        $st->execute();

        $result = $st->fetch();

        $this->resetDebug();

        $this
            ->setDebug('query', preg_replace('/\s+/', ' ', sprintf($query)))
            ->setDebug('time', microtime(true) - $start)
            ->setDebug('filters', count($options['filters']))
            ->setDebug('joins', substr_count($query, 'JOIN'))
        ;

        return (int) (isset($result['result'])) ? $result['result'] : 0;
    }

    /**
     * Get Unique Document ID.
     */
    public function getUid()
    {
        $unique = uniqid();
        $attempts = 5;

        for ($i = 1; $i <= $attempts; ++$i) {
            $document = $this->getDocument($unique);

            if (empty($document) || $document['$uid'] !== $unique) {
                return $unique;
            }
        }

        throw new Exception('Failed to create a unique ID ('.$attempts.' attempts)');
    }

    /**
     * Last Modified.
     *
     * Return unix timestamp of last time a node queried in corrent session has been changed
     *
     * @return int
     */
    public function lastModified()
    {
        return $this->lastModified;
    }

    /**
     * Parse Filter.
     *
     * @param string $filter
     *
     * @return array
     *
     * @throws Exception
     */
    protected function parseFilter($filter)
    {
        $operatorsMap = ['!=', '>=', '<=', '=', '>', '<']; // Do not edit order of this array

        //FIXME bug with >= <= operators

        $operator = null;

        foreach ($operatorsMap as $node) {
            if (strpos($filter, $node) !== false) {
                $operator = $node;
                break;
            }
        }

        if (empty($operator)) {
            throw new Exception('Invalid operator');
        }

        $filter = explode($operator, $filter);

        if (count($filter) != 2) {
            throw new Exception('Invalid filter expression');
        }

        return [
            'key' => $filter[0],
            'value' => $filter[1],
            'operator' => $operator,
        ];
    }

    /**
     * Get Data Type.
     *
     * Check value data type. return value can be on of the following:
     * string, integer, float, boolean, object, list or null
     *
     * @param $value
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getDataType($value)
    {
        switch (gettype($value)) {

            case 'string':
                return self::DATA_TYPE_STRING;
                break;

            case 'integer':
                return self::DATA_TYPE_INTEGER;
                break;

            case 'double':
                return self::DATA_TYPE_FLOAT;
                break;

            case 'boolean':
                return self::DATA_TYPE_BOOLEAN;
                break;

            case 'array':
                if ((bool) count(array_filter(array_keys($value), 'is_string'))) {
                    return self::DATA_TYPE_DICTIONARY;
                }

                return self::DATA_TYPE_ARRAY;
                break;

            case 'NULL':
                return self::DATA_TYPE_NULL;
                break;
        }

        throw new Exception('Unknown data type: '.$value.' ('.gettype($value).')');
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function setDebug($key, $value)
    {
        $this->debug[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * return $this;.
     */
    public function resetDebug()
    {
        $this->debug = [];
    }

    /**
     * @return PDO
     *
     * @throws Exception
     */
    protected function getPDO():PDO
    {
        return $this->register->get('db');
    }

    /**
     * @throws Exception
     *
     * @return Client
     */
    protected function getRedis():Client
    {
        return $this->register->get('cache');
    }
}
