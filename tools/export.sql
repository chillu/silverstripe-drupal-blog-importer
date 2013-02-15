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
	-- 'dst',
	'blog_coid',
	'blog_title',
	'blog_path',
	'totalcount',
	'tags'
UNION ALL
SELECT 
	cp_node.nid,
	cp_node.title,
	cp_node.uid,
	usernodes.title AS author_title,
	cp_node.status,
	FROM_UNIXTIME(cp_node.created) AS created,
	FROM_UNIXTIME(cp_node.changed) AS changed,
	REPLACE(cp_node_revisions.body, '\n', '') AS body,
	REPLACE(cp_node_revisions.teaser, '\n', '') AS teaser,
	cp_node_revisions.vid,
	-- cp_url_alias.dst,
	cp_column_list.coid AS blog_coid,
	cp_column_list.title AS blog_title,
	cp_column_list.path AS blog_path,
	cp_node_counter.totalcount,
	GROUP_CONCAT(cp_term_data.name)
FROM 
	cp_node
	LEFT JOIN cp_node_revisions ON cp_node_revisions.nid = cp_node.nid AND cp_node_revisions.vid = cp_node.vid
	LEFT JOIN cp_column_node ON cp_column_node.nid = cp_node.nid
	LEFT JOIN cp_column_list ON cp_column_node.coid = cp_column_list.coid
	LEFT JOIN cp_node_counter ON cp_node.nid = cp_node_counter.nid
	LEFT JOIN cp_term_node ON cp_term_node.nid = cp_node.nid
	LEFT JOIN cp_term_data ON cp_term_data.tid = cp_term_node.tid
	LEFT JOIN cp_vocabulary_node_types ON cp_vocabulary_node_types.vid = cp_term_data.vid
	LEFT JOIN cp_vocabulary ON cp_vocabulary.vid = cp_vocabulary_node_types.vid
	-- LEFT JOIN cp_url_alias ON cp_url_alias.src = CONCAT('node/', cp_node.nid)
	LEFT JOIN cp_node as usernodes ON usernodes.uid = cp_node.uid AND usernodes.type = 'usernode'
WHERE 
	cp_vocabulary_node_types.type = 'column'
	AND cp_node.type = 'column'
GROUP BY
	cp_node.nid
INTO OUTFILE '/tmp/posts.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\n';

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
	cp_comments.cid,
	cp_comments.nid,
	cp_comments.uid,
	cp_comments.subject,
	REPLACE(cp_comments.comment, '\n', '') AS comment,
	cp_comments.hostname,
	cp_comments.timestamp,
	cp_comments.name,
	cp_comments.mail,
	cp_comments.homepage
FROM 
	cp_comments
INTO OUTFILE '/tmp/comments.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\n';