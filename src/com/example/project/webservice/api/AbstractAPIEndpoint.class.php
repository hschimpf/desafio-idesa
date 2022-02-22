<?php
    namespace com\example\project\webservice\api;

    use net\hdssolutions\php\rest\endpoint\AbstractObjectOrientedEndpoint;

    abstract class AbstractAPIEndpoint extends AbstractObjectOrientedEndpoint {
        protected final function getTransaction() {
            // return current parent transaction
            return $this->parent()->getTransaction();
        }

        protected final function startTransaction() {
            // return parent action
            return $this->parent()->startTransaction();
        }

        protected final function commitTransaction() {
            // return parent action
            return $this->parent()->commitTransaction();
        }

        protected final function rollbackTransaction() {
            // return parent action
            return $this->parent()->rollbackTransaction();
        }
    }