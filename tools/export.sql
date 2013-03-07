-- Export users
SELECT
	'nid',
	'uid',
	'title',
	'name',
	'mail',
	'created',
	'changed'
UNION ALL
SELECT
	node.nid,
	node.uid,
	node.title,
	users.name,
	users.mail,
	FROM_UNIXTIME(node.created-3600) AS 'created',
	FROM_UNIXTIME(node.changed-3600) AS 'changed'
FROM 
	LEFT JOIN node ON users.uid = node.uid
	LEFT JOIN comments ON comments.uid = node.uid
	LEFT JOIN node AS blogposts ON blogposts.uid = node.uid AND blogposts.type = 'column'
WHERE
	node.type = 'usernode'
	AND (
		comments.nid IS NOT NULL
		OR blogposts.nid IS NOT NULL
	)
GROUP BY node.uid
INTO OUTFILE '/tmp/users.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\r\n';

-- Export posts
SET NAMES utf8;
SELECT
	'nid',
	'title',
	'uid',
	'author_title',
	'status',
	'created',
	'changed',
	'body',
	'teaser',
	'vid',
	'dst',
	'blog_coid',
	'blog_title',
	'blog_path',
	'totalcount',
	'tags'
UNION ALL
SELECT 
	node.nid,
	node.title,
	node.uid,
	usernodes.title AS author_title,
	node.status,
	FROM_UNIXTIME(node.created) AS created,
	FROM_UNIXTIME(node.changed) AS changed,
	REPLACE(IFNULL(node_revisions.body, ''), '\r\n', '\n') AS body,
	REPLACE(IFNULL(node_revisions.teaser, ''), '\r\n', '\n') AS teaser,
	node_revisions.vid,
	url_alias.dst,
	column_list.coid AS blog_coid,
	column_list.title AS blog_title,
	column_list.path AS blog_path,
	node_counter.totalcount,
	GROUP_CONCAT(term_data.name)
FROM 
	node
	LEFT JOIN node_revisions ON node_revisions.nid = node.nid AND node_revisions.vid = node.vid
	LEFT JOIN column_node ON column_node.nid = node.nid
	LEFT JOIN column_list ON column_node.coid = column_list.coid
	LEFT JOIN node_counter ON node.nid = node_counter.nid
	LEFT JOIN term_node ON term_node.nid = node.nid
	LEFT JOIN term_data ON term_data.tid = term_node.tid
	LEFT JOIN vocabulary_node_types ON vocabulary_node_types.vid = term_data.vid
	LEFT JOIN vocabulary ON vocabulary.vid = vocabulary_node_types.vid
	LEFT JOIN url_alias ON url_alias.src = CONCAT('node/', node.nid)
	LEFT JOIN node as usernodes ON usernodes.uid = node.uid AND usernodes.type = 'usernode'
WHERE 
	vocabulary_node_types.type = 'column'
	AND node.type = 'column'
GROUP BY
	node.nid
INTO OUTFILE '/tmp/posts.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\r\n';

-- Export comments
SELECT
	'cid',
	'nid',
	'uid',
	'subject',
	'comment',
	'hostname',
	'timestamp',
	'name',
	'mail',
	'homepage'
UNION ALL
SELECT
	comments.cid,
	comments.nid,
	comments.uid,
	REPLACE(IFNULL(comments.subject, ''), '\r\n', '\n') AS subject,
	REPLACE(IFNULL(comments.comment, ''), '\r\n', '\n') AS comment,
	comments.hostname,
	FROM_UNIXTIME(comments.timestamp) AS 'timestamp',
	comments.name,
	comments.mail,
	comments.homepage
FROM 
	comments
INTO OUTFILE '/tmp/comments.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\r\n';